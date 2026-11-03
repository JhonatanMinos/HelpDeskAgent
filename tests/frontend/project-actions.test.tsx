import { expect, test } from 'bun:test';

function runActionScenario(scenario: string) {
	const script = `
		import { mock, expect } from 'bun:test';
		const React = await import('react');
		let action = null;
		let formIndex = 0;
		const forms = [];
		const requests = [];
		mock.module('react', () => ({ ...React,
			useId: () => 'fixture', useState: () => [action, (value) => { action = value; }],
		}));
		mock.module('@inertiajs/react', () => ({ useForm: (initial) => {
			const index = formIndex++;
			if (!forms[index]) forms[index] = {
				data: initial, errors: {}, processing: false,
				setData(key, value) { this.data = typeof key === 'string' ? {...this.data, [key]: value} : key; },
				clearErrors() { this.errors = {}; },
				patch(url, options) { requests.push({method: 'patch', url, data: this.data, options}); },
				delete(url, options) { requests.push({method: 'delete', url, data: this.data, options}); },
			};
			return forms[index];
		} }));
		const { ProjectActions } = await import('./resources/js/components/dev/project-actions.tsx');
		const project = { id: 4, name: 'Network refresh', status: 'pausado',
			Initial_requirements: 'Existing requirements', estimated_date_delivery: '2020-01-01T00:00:00.000000Z' };
		function nodes(node) {
			if (!node || typeof node !== 'object') return [];
			if (Array.isArray(node)) return node.flatMap(nodes);
			return [node, ...nodes(node.props?.children)];
		}
		function render() { formIndex = 0; return nodes(ProjectActions({project, teamSlug: 'local-team'})); }
		function button(label) { return render().find(node => node.props?.['aria-label'] === label); }
		function submit() { render().find(node => node.type === 'form').props.onSubmit({preventDefault() {}}); }
		${scenario}
	`;
	const result = Bun.spawnSync(['bun', '--eval', script], { cwd: process.cwd() });
	expect(result.exitCode, result.stderr.toString()).toBe(0);
}

test('edit opens with persisted metadata and submits the edited data to its team scoped route', () => {
	runActionScenario(`
		button('Editar Network refresh').props.onClick();
		expect(forms[0].data).toEqual({name: 'Network refresh', requirements: 'Existing requirements', due_date: '2020-01-01'});
		const input = render().find(node => node.props?.id === 'fixture-name');
		input.props.onChange({target: {value: 'Updated project'}});
		submit();
		expect(requests).toHaveLength(1);
		expect(requests[0]).toMatchObject({method: 'patch', url: '/local-team/dev/projects/4',
			data: {name: 'Updated project', requirements: 'Existing requirements', due_date: '2020-01-01'}});
		forms[0].errors = {name: 'Invalid name'};
		expect(action).toBe('edit');
		expect(render().some(node => node.props?.role === 'alert' && node.props.children === 'Invalid name')).toBe(true);
		requests[0].options.onSuccess();
		expect(action).toBe(null);
	`);
});

test('status exposes all four options and reuses the existing status endpoint', () => {
	runActionScenario(`
		button('Cambiar estado de Network refresh').props.onClick();
		expect(forms[1].data.status).toBe('pausado');
		expect(render().filter(node => node.type === 'option').map(node => node.props.value))
			.toEqual(['propuesto', 'activo', 'pausado', 'completado']);
		render().find(node => node.type === 'select').props.onChange({target: {value: 'completado'}});
		submit();
		expect(requests[0]).toMatchObject({method: 'patch', url: '/local-team/dev/projects/4/status', data: {status: 'completado'}});
	`);
});

test('delete requires confirmation, cancel sends nothing and pending requests cannot repeat', () => {
	runActionScenario(`
		button('Eliminar Network refresh').props.onClick();
		expect(requests).toHaveLength(0);
		expect(render().some(node => typeof node.props?.children === 'string' && node.props.children.includes('todas sus tareas'))).toBe(true);
		render().find(node => node.props?.children === 'Cancelar').props.onClick();
		expect(action).toBe(null);
		expect(requests).toHaveLength(0);
		button('Eliminar Network refresh').props.onClick();
		submit();
		expect(requests[0]).toMatchObject({method: 'delete', url: '/local-team/dev/projects/4'});
		forms[2].processing = true;
		submit();
		expect(requests).toHaveLength(1);
		expect(render().find(node => node.props?.open === true).props.onOpenChange(false)).toBe(false);
		expect(action).toBe('delete');
		expect(button('Eliminar Network refresh').props.disabled).toBe(true);
	`);
});
