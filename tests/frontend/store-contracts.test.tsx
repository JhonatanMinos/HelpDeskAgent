import { beforeEach, expect, mock, test } from 'bun:test';
import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

type TeamContext = { id: number; slug: string };
const team: TeamContext = { id: 1, slug: 'local-team' };
const page: {
	props: { auth: { user: { current_team: TeamContext } }; currentTeam: TeamContext | null };
} = { props: { auth: { user: { current_team: team } }, currentTeam: team } };

mock.module('@inertiajs/react', () => ({
	usePage: () => page,
	Head: () => null,
	Link: ({ href, children }: { href: string | { url: string }; children: ReactNode }) => (
		<a href={typeof href === 'string' ? href : href.url}>{children}</a>
	),
	router: {
		post: () => {
			throw new Error('Unexpected network request during SSR');
		},
	},
}));

const wrapper = ({ children }: { children?: ReactNode }) => <div>{children}</div>;
mock.module('@/components/ui/sidebar', () =>
	Object.fromEntries(
		[
			'Sidebar',
			'SidebarContent',
			'SidebarFooter',
			'SidebarHeader',
			'SidebarMenu',
			'SidebarMenuItem',
			'SidebarMenuButton',
		].map((name) => [name, wrapper])
	)
);
mock.module('@/components/app-logo', () => ({ default: () => null }));
mock.module('@/components/nav-user', () => ({ NavUser: () => null }));
mock.module('@/components/team-switcher', () => ({ TeamSwitcher: () => null }));
mock.module('@/components/nav-footer', () => ({ NavFooter: () => null }));
mock.module('@/components/nav-main', () => ({
	NavMain: ({ items }: { items: { title: string; href: string }[] }) => (
		<nav>
			{items.map((item) => (
				<a key={item.title} href={item.href}>
					{item.title}
				</a>
			))}
		</nav>
	),
}));

const { default: ShowStore } = await import('../../resources/js/pages/branches/show');
const { getStoreColumns } = await import('../../resources/js/tables/store-columns');
const { default: TableGeneric } = await import('../../resources/js/components/ui/tableG');
const { AppSidebar } = await import('../../resources/js/components/app-sidebar');
const { useReactTable, getCoreRowModel } = await import('@tanstack/react-table');

const props = {
	store: {
		id: 1,
		name: 'Local store',
		store_code: 'S0001',
		company: 16,
		base_ip: '192.168.1.100',
		status: 'online' as const,
		state: 'Example State',
		address: '1 Main Street',
		city: 'Example City',
		neighborhood: 'Center',
		postal_code: '12345',
		last_seen_at: null,
	},
	deviceIps: {
		vpn: '192.168.1.1',
		checador: '192.168.1.107',
		servidor: '192.168.1.100',
		caja_1: '192.168.1.101',
		caja_2: '192.168.1.102',
	},
	printers: [{ id: 1, name: 'Receipt printer', modelo: 'TM-T20', type_conection: 'USB' }],
	recentPings: [],
};

beforeEach(() => {
	page.props.currentTeam = team;
});

test('branch details render the backend IP and printer fields', () => {
	const html = renderToStaticMarkup(<ShowStore {...props} />);
	for (const field of ['Receipt printer', 'TM-T20', 'USB', '192.168.1.101']) {
		expect(html).toContain(field);
	}
});

test('branch network topology renders populated ping history', () => {
	for (const recentPings of [
		[{ device_label: 'vpn', is_online: true, latency_ms: 650, checked_at: '2026-01-01T12:00:00Z' }],
		[
			{
				device_label: 'servidor',
				is_online: false,
				latency_ms: null,
				checked_at: '2026-01-01T12:00:00Z',
			},
		],
	]) {
		const html = renderToStaticMarkup(<ShowStore {...props} recentPings={recentPings} />);
		expect(html).toContain('Receipt printer');
		expect(html).toContain('192.168.1.100');
	}
});

test('store table preserves the business fields', () => {
	function StoreTable() {
		const table = useReactTable({
			data: [props.store],
			columns: getStoreColumns('local-team'),
			getCoreRowModel: getCoreRowModel(),
		});
		return <TableGeneric table={table} />;
	}
	const html = renderToStaticMarkup(<StoreTable />);
	for (const field of ['Local store', 'S0001', '192.168.1.100', 'online', 'Example State']) {
		expect(html).toContain(field);
	}
});

test('sidebar routes use the active team and safely fall back without one', () => {
	const html = renderToStaticMarkup(<AppSidebar />);
	for (const suffix of ['dashboard', 'branches', 'dev/projects', 'tickets']) {
		expect(html).toContain(`href="/local-team/${suffix}"`);
	}
	page.props.currentTeam = null;
	const fallback = renderToStaticMarkup(<AppSidebar />);
	expect(fallback).toContain('href="/"');
	expect(fallback).not.toContain('undefined');
});

test('branch breadcrumb retains its team-scoped route', () => {
	const layout = ShowStore.layout({ currentTeam: team, store: props.store });
	expect(layout.breadcrumbs[1].href).toBe('/local-team/branches/1');
});
