import { expect, test } from 'bun:test';
import { readFileSync } from 'node:fs';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { renderToStaticMarkup } from 'react-dom/server';
import { PaginationGeneric } from '../../resources/js/components/paginationUI';
import TableGeneric from '../../resources/js/components/ui/tableG';

test('pagination disables unavailable destinations and does not offer an inert page-size selector', () => {
	const html = renderToStaticMarkup(
		<PaginationGeneric
			pagination={{ prev_page_url: null, next_page_url: '/local-team/branches?page=2' }}
		/>
	);
	expect(html).toContain('aria-disabled="true"');
	expect(html).toContain('href="/local-team/branches?page=2"');
	expect(html).toContain('Página siguiente');
	expect(html).not.toContain('Rows per page');
	expect(html).not.toContain('role="combobox"');
});

test('pagination renders actual range metadata instead of guessed counts', () => {
	const html = renderToStaticMarkup(
		<PaginationGeneric
			pagination={{
				prev_page_url: '/local-team/branches?page=1',
				next_page_url: null,
				current_page: 2,
				last_page: 2,
				from: 11,
				to: 12,
				total: 12,
			}}
		/>
	);
	expect(html).toContain('11–12 de 12');
	expect(html).toContain('Página 2 de 2');
	expect(html).toContain('href="/local-team/branches?page=1"');
});

test('empty tables explain the result and populated tables retain every field', () => {
	function Fixture({ populated }: { populated: boolean }) {
		const table = useReactTable({
			data: populated ? [{ name: 'Central branch', code: 'S001' }] : [],
			columns: [
				{ accessorKey: 'name', header: 'Name' },
				{ accessorKey: 'code', header: 'Code' },
			],
			getCoreRowModel: getCoreRowModel(),
		});
		return (
			<TableGeneric
				table={table}
				emptyTitle="No matching branches"
				emptyDescription="Try another name or code."
			/>
		);
	}
	const empty = renderToStaticMarkup(<Fixture populated={false} />);
	expect(empty).toContain('No matching branches');
	expect(empty).toContain('Try another name or code.');
	expect(empty).toContain('colSpan="2"');
	const full = renderToStaticMarkup(<Fixture populated />);
	expect(full).toContain('Central branch');
	expect(full).toContain('S001');
	expect(full).not.toContain('No matching branches');
});

test('shell exposes a keyboard skip target and errors use visible text tokens in both themes', () => {
	const shell = readFileSync(
		new URL('../../resources/js/layouts/app/app-sidebar-layout.tsx', import.meta.url),
		'utf8'
	);
	const css = readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8');
	expect(shell).toContain('href="#workspace-content"');
	expect(shell).toContain('id="workspace-content"');
	expect(css.match(/--destructive-foreground: var\(--destructive\)/g)).toHaveLength(2);
});
