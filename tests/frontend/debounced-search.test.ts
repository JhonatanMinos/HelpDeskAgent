import { expect, test } from 'bun:test';

/**
 * Runs an isolated react-mocked scenario around useDebouncedSearch.
 *
 * The hook is the only place where a stale dependency list can turn a debounced
 * search into either a stale request or an infinite refetch loop, so the
 * scenario asserts the effect contract rather than rendering the whole page.
 */
function runHook(scenario: string) {
	const script = `
		import {mock, expect} from 'bun:test';
		const React = await import('react');
		const requests = [], refs = [];
		let slot = 0;
		const timers = [];
		globalThis.setTimeout = fn => {timers.push(fn); return timers.length;};
		globalThis.clearTimeout = () => {};
		mock.module('react', () => ({
			...React,
			useState: initial => [initial, () => {}],
			useRef: initial => {
				const current = slot++;
				if (!(current in refs)) refs[current] = {current: initial};
				return refs[current];
			},
			useEffect: (fn, deps) => {effects.push({fn, deps});},
		}));
		mock.module('@inertiajs/react', () => ({
			router: {get: (url, data, options) => requests.push({url, data, options})},
		}));
		const {useDebouncedSearch} = await import('./resources/js/hooks/use-debounced-search.ts');
		let effects = [];
		function render(routeUrl, only, delayMs) {
			slot = 0;
			effects = [];
			useDebouncedSearch(routeUrl, '', only, delayMs);
			return effects.slice();
		}
		/* React runs every effect after the commit; the debounce effect is declared last. */
		function commit(pass) {
			pass.forEach(effect => effect.fn());
			return pass[pass.length - 1];
		}
		function flush() {
			timers.splice(0, timers.length).forEach(fn => fn());
		}
		${scenario}
	`;
	const result = Bun.spawnSync(['bun', '--eval', script], { cwd: process.cwd() });
	expect(result.exitCode, result.stderr.toString()).toBe(0);
}

test('the debounce effect does not depend on the identity of the only array', () => {
	runHook(`
		const first = render('/stores', ['stores', 'filters'], 350);
		const second = render('/stores', ['stores', 'filters'], 350);
		const deps = commit(first).deps;
		expect(deps).toEqual(commit(second).deps);
		expect(deps).toHaveLength(3);
		expect(deps[0]).toBe('');
		expect(deps[1]).toBe('/stores');
		expect(deps[2]).toBe(350);
	`);
});

test('the debounce effect reacts to a changed route url', () => {
	runHook(`
		expect(commit(render('/stores', ['stores'], 350)).deps[1]).toBe('/stores');
		expect(commit(render('/branches', ['stores'], 350)).deps[1]).toBe('/branches');
	`);
});

test('the debounced request forwards the latest only list', () => {
	runHook(`
		commit(render('/stores', ['stores'], 350));
		commit(render('/stores', ['stores', 'filters'], 350));
		flush();
		expect(requests).toHaveLength(1);
		expect(requests[0].url).toBe('/stores');
		expect(requests[0].options.only).toEqual(['stores', 'filters']);
	`);
});
