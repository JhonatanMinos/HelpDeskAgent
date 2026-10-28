import { expect, test } from 'bun:test';
import { renderToStaticMarkup } from 'react-dom/server';
import { DashboardSummary } from '../../resources/js/components/dashboard-summary';
import type { DashboardOverview } from '../../resources/js/types/dashboard';

const empty: DashboardOverview = {
	branches: { total: 0, online: 0, offline: 0, unknown: 0 },
	attention: [],
	tickets: { abierto: 0, en_proceso: 0, esperando_cliente: 0, resuelto: 0, cerrado: 0 },
	activity: [],
};

test('dashboard shows honest empty states without fabricated availability', () => {
	const html = renderToStaticMarkup(<DashboardSummary overview={empty} teamSlug="local-team" />);
	for (const label of [
		'Sin sucursales registradas',
		'Sin actividad reciente',
		'Sin datos',
		'/local-team/branches',
	]) {
		expect(html).toContain(label);
	}
	expect(html).not.toContain('NaN');
	expect(html).not.toContain('100%');
});

test('branches dominate the dashboard with status, freshness and working team links', () => {
	const overview: DashboardOverview = {
		...empty,
		branches: { total: 4, online: 2, offline: 1, unknown: 1 },
		attention: [
			{
				id: 4,
				code: 'S004',
				name: 'Central branch',
				city: 'City',
				state: 'State',
				status: 'offline',
				lastCheckedAt: '2026-10-28T12:00:00Z',
			},
			{
				id: 5,
				code: 'S005',
				name: 'North branch',
				city: 'City',
				state: 'State',
				status: 'unknown',
				lastCheckedAt: null,
			},
		],
		activity: [
			{
				id: 1,
				ticketId: 8,
				ticketTitle: 'Printer issue',
				type: 'created',
				createdAt: '2026-10-28T12:00:00Z',
			},
		],
	};
	const html = renderToStaticMarkup(<DashboardSummary overview={overview} teamSlug="local-team" />);
	for (const label of [
		'50%',
		'Sin conexión',
		'Sin verificar',
		'Última comprobación',
		'Sin comprobaciones',
		'Central branch',
		'/local-team/branches/4',
		'/local-team/tickets/8',
		'Printer issue',
	]) {
		expect(html).toContain(label);
	}
	expect(html.indexOf('Sucursales')).toBeLessThan(html.indexOf('Tickets'));
});
