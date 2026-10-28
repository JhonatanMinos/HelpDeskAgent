import { expect, test } from 'bun:test';
import { renderToStaticMarkup } from 'react-dom/server';
import { StoreStatsCards } from '../../resources/js/components/stores/store-stats-cards';
import { StoreToolbar } from '../../resources/js/components/stores/store-toolbar';
import { NetworkTopology } from '../../resources/js/pages/branches/network-topology';

test('branch statistics preserve actual totals and company breakdown', () => {
	const html = renderToStaticMarkup(
		<StoreStatsCards total={8} onlineCount={5} companys={[{ company: 16, total: 8 }]} />
	);
	for (const value of ['Sucursales registradas', 'En línea', 'Compañías', '5', '8', '16']) {
		expect(html).toContain(value);
	}
});

test('branch search has a label and refresh reports its pending state', () => {
	const html = renderToStaticMarkup(
		<StoreToolbar search="Central" onSearchChange={() => {}} onRefresh={() => {}} refreshing />
	);
	expect(html).toContain('aria-label="Buscar sucursal"');
	expect(html).toContain('Central');
	expect(html).toContain('disabled');
	expect(html).toContain('aria-busy="true"');
	expect(html).toContain('Actualizando');
});

test('network topology exposes unknown status without suggesting live monitoring', () => {
	const node = { key: 'vpn', label: 'Router', ip: '192.168.1.1', status: 'unknown' as const };
	const html = renderToStaticMarkup(
		<NetworkTopology vpn={node} checador={node} servidor={node} caja1={node} caja2={node} />
	);
	expect(html).toContain('role="img"');
	expect(html).toContain('Sin verificar');
	expect(html).toContain('192.168.1.1');
	expect(html).not.toContain('animate-pulse');
});
