import { expect, test } from 'bun:test';

function runMove(scenario: string) {
	const script = `
		import {mock, expect} from 'bun:test';
		const React = await import('react');
		const states = [], requests = [];
		let index = 0;
		const task = {id: 8, title: 'New task', priority: 'media', archived_at: null};
		const columns = [{id: 2, name: 'Pending', order: 0, tasks: [task]}, {id: 3, name: 'Doing', order: 1, tasks: []}];
		mock.module('react', () => ({...React, useEffect: () => {}, useState: initial => {
			const slot = index++;
			if (!(slot in states)) states[slot] = initial;
			return [states[slot], value => {states[slot] = typeof value === 'function' ? value(states[slot]) : value;}];
		}}));
		mock.module('@inertiajs/react', () => ({Head: () => null, useForm: () => ({}),
			usePage: () => ({props: {auth: {user: {current_team: {id: 1, slug: 'local-team'}}},
				project: {id: 4, name: 'Project', status: 'activo'}, columns}}),
			router: {patch: (url, data, options) => requests.push({url, data, options})}}));
		const {default: Board} = await import('./resources/js/pages/projects/show.tsx');
		function nodes(node) {if (!node || typeof node !== 'object') return []; if (Array.isArray(node)) return node.flatMap(nodes); return [node, ...nodes(node.props?.children)];}
		function render() {index = 0; return nodes(Board());}
		function drop(column) {
			render().find(node => node.props?.draggable).props.onDragStart();
			render().find(node => node.props?.['aria-label'] === 'Etapa ' + column).props.onDrop();
		}
		${scenario}
	`;
	const result = Bun.spawnSync(['bun', '--eval', script], { cwd: process.cwd() });
	expect(result.exitCode, result.stderr.toString()).toBe(0);
}

test('dropping a task on its current column retains it without a request', () => {
	runMove(
		`drop('Pending'); expect(states[0][0].tasks).toEqual([task]); expect(requests).toHaveLength(0);`
	);
});

test('moving a task retains the existing team route and destination order payload', () => {
	runMove(`drop('Doing'); expect(states[0][0].tasks).toHaveLength(0); expect(states[0][1].tasks).toEqual([task]);
		expect(requests[0]).toMatchObject({url: '/local-team/dev/tasks/8/move', data: {boards_column_id: 3, ordered_task_ids: [8]}});`);
});

test('rejected and cancelled moves restore the previous visible board', () => {
	runMove(`drop('Doing'); requests[0].options.onError({ordered_task_ids: 'Rejected'});
		expect(states[0]).toEqual(columns); requests[0].options.onFinish();
		drop('Doing'); requests[1].options.onCancel(); expect(states[0]).toEqual(columns);`);
});

test('a pending move disables card dragging until the request finishes', () => {
	runMove(`drop('Doing'); expect(render().some(node => node.props?.draggable === true)).toBe(false);
		requests[0].options.onFinish(); expect(render().some(node => node.props?.draggable === true)).toBe(true);`);
});
