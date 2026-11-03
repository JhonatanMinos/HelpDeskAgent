import { expect, test } from 'bun:test';

// Run each SSR fixture in a fresh process: Inertia mocks must not leak to sibling suites.
function renderWorkspace(page: string, props: Record<string, unknown>) {
	const script = `
		import { mock } from 'bun:test';
		import { createElement } from 'react';
		import { renderToStaticMarkup } from 'react-dom/server';
		const props = ${JSON.stringify({ auth: { user: { current_team: { id: 1, slug: 'local-team' } } }, ...props })};
		mock.module('@inertiajs/react', () => ({
			Head: () => null,
			Link: ({href, children, ...rest}) => createElement('a', {href: typeof href === 'string' ? href : href.url, ...rest}, children),
			usePage: () => ({props}),
			useForm: (data) => ({data, errors: {}, processing: false, setData() {}, post() {}, patch() {}, reset() {}}),
			router: {patch() {}},
		}));
		const { default: Page } = await import('./resources/js/pages/${page}.tsx');
		console.log(renderToStaticMarkup(createElement(Page)));
	`;
	const result = Bun.spawnSync(['bun', '--eval', script], { cwd: process.cwd() });
	expect(result.stderr.toString()).not.toContain('error:');
	expect(result.exitCode, result.stderr.toString()).toBe(0);
	return result.stdout.toString();
}

test('ticket creation keeps its fields and gives each visible label an input target', () => {
	const html = renderWorkspace('tickets/index', {
		tickets: { data: [], prev_page_url: null, next_page_url: null },
		stores: [],
		projects: [],
		teamMembers: [],
	});
	for (const field of [
		'title',
		'description',
		'category',
		'priority',
		'store_id',
		'assigned_to',
		'due_date',
	]) {
		expect(html).toContain(`for="ticket-create-${field}"`);
		expect(html).toContain(`id="ticket-create-${field}"`);
	}
	expect(html).toContain('No hay tickets todavía.');
	expect(html).toContain('Crear ticket');
	expect(html).toContain('Centro de soporte');
});

test('ticket detail retains case data, status options and accessible update and comment fields', () => {
	const html = renderWorkspace('tickets/show', {
		ticket: {
			id: 7,
			title: 'Printer issue',
			description: 'Paper jam',
			status: 'abierto',
			priority: 'alta',
			category: 'impresoras',
			due_date: null,
			store: { id: 1, name: 'Central branch' },
			project: null,
			assignee: null,
			comments: [],
			activities: [],
		},
		stores: [],
		projects: [],
		teamMembers: [],
	});
	for (const field of [
		'title',
		'description',
		'status',
		'category',
		'priority',
		'store_id',
		'project_id',
		'assigned_to',
		'due_date',
	]) {
		expect(html).toContain(`for="ticket-update-${field}"`);
		expect(html).toContain(`id="ticket-update-${field}"`);
	}
	for (const value of [
		'Printer issue',
		'Paper jam',
		'Central branch',
		'abierto',
		'en_proceso',
		'esperando_cliente',
		'resuelto',
		'cerrado',
		'/local-team/tickets',
		'Guardar cambios',
		'Agregar seguimiento',
	]) {
		expect(html).toContain(value);
	}
	expect(html).toContain('for="ticket-comment"');
	expect(html).toContain('id="ticket-comment"');
});

test('project workspace preserves project metadata and team scoped detail links', () => {
	const html = renderWorkspace('projects/index', {
		projects: [
			{
				id: 4,
				name: 'Network refresh',
				status: 'activo',
				tasks_count: 3,
				creator: { name: 'Case owner' },
				due_date: '2026-10-29',
			},
		],
	});
	for (const value of [
		'Network refresh',
		'Activo',
		'3',
		'tareas',
		'Case owner',
		'Entrega:',
		'/local-team/dev/projects/4',
		'Nuevo proyecto',
		'Espacio de trabajo',
	]) {
		expect(html).toContain(value);
	}
});

test('project cards expose separate edit, status and delete controls outside the board link', () => {
	const html = renderWorkspace('projects/index', {
		projects: [
			{
				id: 4,
				name: 'Network refresh',
				status: 'activo',
				tasks_count: 3,
				creator: { name: 'Case owner' },
				estimated_date_delivery: '2026-10-29',
				Initial_requirements: 'Keep existing network',
			},
		],
	});
	for (const label of [
		'Editar Network refresh',
		'Cambiar estado de Network refresh',
		'Eliminar Network refresh',
	]) {
		expect(html).toContain(`aria-label="${label}"`);
	}
	expect(html).not.toMatch(/<a\b[^>]*>(?:(?!<\/a>)[\s\S])*<button\b/);
	expect(html).toContain('/local-team/dev/projects/4');
});

test('Kanban retains tasks and shows a useful empty column state', () => {
	const html = renderWorkspace('projects/show', {
		project: { id: 4, name: 'Network refresh', status: 'activo' },
		columns: [
			{ id: 1, name: 'Pending', order: 0, tasks: [] },
			{
				id: 2,
				name: 'Doing',
				order: 1,
				tasks: [{ id: 8, title: 'Review VPN', priority: 'alta', assignee: null }],
			},
		],
	});
	for (const value of [
		'Network refresh',
		'Pending',
		'Doing',
		'Review VPN',
		'alta',
		'Nueva tarea',
		'Sin tareas en esta columna',
		'Tablero del equipo',
		'Acciones de Review VPN',
		'Tareas archivadas (0)',
	]) {
		expect(html).toContain(value);
	}
});
