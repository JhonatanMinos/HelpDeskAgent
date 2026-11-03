import { expect, test } from 'bun:test';

function runTaskScenario(scenario: string) {
	const script = `
		import { mock, expect } from 'bun:test';
		const React = await import('react');
		let action = null;
		let archivingAvailable = true;
		let formIndex = 0;
		const forms = [], requests = [];
		mock.module('react', () => ({...React, useId: () => 'task-fixture',
			useState: () => [action, value => {action = value;}]}));
		mock.module('@inertiajs/react', () => ({useForm: initial => {
			const index = formIndex++;
			if (!forms[index]) forms[index] = {data: initial, errors: {}, processing: false,
				setData(key, value) {this.data = typeof key === 'string' ? {...this.data, [key]: value} : key;},
				clearErrors() {this.errors = {};},
				patch(url, options) {requests.push({method: 'patch', url, data: this.data, options});},
				delete(url, options) {requests.push({method: 'delete', url, data: this.data, options});}
			};
			return forms[index];
		}}));
		const {TaskActions} = await import('./resources/js/components/dev/task-actions.tsx');
		const task = {id: 8, title: 'Review VPN', description: 'Existing details', priority: 'alta',
			assigned_to: 7, limit_date: '2020-01-01T00:00:00Z', archived_at: null};
		const members = [{id: 7, name: 'Existing assignee'}, {id: 9, name: 'Other member'}];
		function nodes(node) {
			if (!node || typeof node !== 'object') return [];
			if (Array.isArray(node)) return node.flatMap(nodes);
			return [node, ...nodes(node.props?.children)];
		}
		function render() {formIndex = 0; return nodes(TaskActions({task, teamSlug: 'local-team', members, archivingAvailable}));}
		function choose(label) {render().find(node => node.props?.['aria-label'] === label).props.onSelect();}
		function submit() {render().find(node => node.type === 'form').props.onSubmit({preventDefault() {}});}
		${scenario}
	`;
	const result = Bun.spawnSync(['bun', '--eval', script], { cwd: process.cwd() });
	expect(result.exitCode, result.stderr.toString()).toBe(0);
}

test('task edit preloads all fields and sends metadata only to its scoped route', () => {
	runTaskScenario(`
		choose('Editar Review VPN');
		expect(forms[0].data).toEqual({title: 'Review VPN', description: 'Existing details', priority: 'alta', due_date: '2020-01-01', assigned_to: '7'});
		render().find(node => node.props?.id === 'task-fixture-title').props.onChange({target: {value: 'Edited task'}});
		render().find(node => node.props?.id === 'task-fixture-assignee').props.onChange({target: {value: '9'}});
		submit();
		expect(requests[0]).toMatchObject({method: 'patch', url: '/local-team/dev/tasks/8', data: {title: 'Edited task', assigned_to: '9'}});
		forms[0].errors = {title: 'Invalid title'};
		expect(render().some(node => node.props?.role === 'alert' && node.props.children === 'Invalid title')).toBe(true);
		expect(action).toBe('edit');
		requests[0].options.onSuccess();
		expect(action).toBe(null);
	`);
});

test('unavailable archiving cannot hide tasks or prevent editing and deletion', () => {
	runTaskScenario(`
		archivingAvailable = false;
		expect(render().find(node => node.props?.['aria-label'] === 'Archivar Review VPN').props.disabled).toBe(true);
		choose('Archivar Review VPN');
		expect(action).toBe(null);
		expect(requests).toHaveLength(0);
		choose('Editar Review VPN'); expect(action).toBe('edit');
		choose('Eliminar Review VPN'); expect(action).toBe('delete');
	`);
});

test('archive is reversible and archived tasks offer restore rather than archive', () => {
	runTaskScenario(`
		choose('Archivar Review VPN');
		expect(requests).toHaveLength(0);
		expect(render().some(node => typeof node.props?.children === 'string' && node.props.children.includes('restaurar'))).toBe(true);
		submit();
		expect(requests[0]).toMatchObject({method: 'patch', url: '/local-team/dev/tasks/8/archive', data: {}});
		requests[0].options.onSuccess();
		task.archived_at = '2026-10-29T00:00:00Z';
		expect(render().some(node => node.props?.['aria-label'] === 'Archivar Review VPN')).toBe(false);
		choose('Restaurar Review VPN');
		submit();
		expect(requests[1]).toMatchObject({method: 'patch', url: '/local-team/dev/tasks/8/restore'});
	`);
});

test('deletion is confirmed, cancel sends nothing and pending actions cannot be repeated', () => {
	runTaskScenario(`
		choose('Eliminar Review VPN');
		expect(render().some(node => typeof node.props?.children === 'string' && node.props.children.includes('no se puede deshacer'))).toBe(true);
		render().find(node => node.props?.children === 'Cancelar').props.onClick();
		expect(requests).toHaveLength(0);
		expect(action).toBe(null);
		choose('Eliminar Review VPN');
		submit();
		expect(requests[0]).toMatchObject({method: 'delete', url: '/local-team/dev/tasks/8'});
		forms[1].processing = true;
		submit();
		expect(requests).toHaveLength(1);
		render().find(node => node.props?.open === true).props.onOpenChange(false);
		expect(action).toBe('delete');
	`);
});

test('board keeps active cards draggable and exposes archived tasks with their own actions', () => {
	const script = `
		import {mock, expect} from 'bun:test';
		import {createElement} from 'react';
		import {renderToStaticMarkup} from 'react-dom/server';
		const task = {id: 8, title: 'Active task', description: null, priority: 'media', assigned_to: null,
			limit_date: null, archived_at: null, assignee: null};
		mock.module('@inertiajs/react', () => ({Head: () => null, router: {patch() {}},
			useForm: data => ({data, errors: {}, processing: false}),
			usePage: () => ({props: {
				auth: {user: {current_team: {id: 1, slug: 'local-team'}}},
				project: {id: 4, name: 'Network refresh', status: 'activo'},
				columns: [{id: 2, name: 'Doing', order: 0, tasks: [task]}],
				members: [{id: 7, name: 'Team member'}],
				archivedTasks: [{...task, id: 9, title: 'Archived task', archived_at: '2026-10-29', column: {name: 'Doing'}}]
			}})}));
		const {default: Board} = await import('./resources/js/pages/projects/show.tsx');
		const html = renderToStaticMarkup(createElement(Board));
		expect(html).toContain('Tareas archivadas (1)');
		expect(html).toContain('Acciones de Active task');
		expect(html).toContain('Acciones de Archived task');
		expect(html.match(/draggable="true"/g)).toHaveLength(1);
		expect(html).toContain('Nueva tarea');
	`;
	const result = Bun.spawnSync(['bun', '--eval', script], { cwd: process.cwd() });
	expect(result.exitCode, result.stderr.toString()).toBe(0);
});
