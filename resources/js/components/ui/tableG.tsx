import type { Table } from '@tanstack/react-table';
import { flexRender } from '@tanstack/react-table';
import {
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
	Table as UITable,
} from '@/components/ui/table';

interface TableGenericProps<T> {
	table: Table<T>;
	rowClassName: (row: Row<TData>) => string;
}

export default function TableGeneric<T>({ table, rowClassName }: TableGenericProps<T>) {
	return (
		<div className="overflow-x-auto overflow-y-auto rounded-lg border">
			<UITable className="table-auto border">
				<TableHeader className="bg-muted">
					{table.getHeaderGroups().map((headerGroup) => (
						<TableRow className="transition-colors hover:bg-white/5" key={headerGroup.id}>
							{headerGroup.headers.map((header) => (
								<TableHead key={header.id} className="px-4 py-2 text-center">
									{flexRender(header.column.columnDef.header, header.getContext())}
								</TableHead>
							))}
						</TableRow>
					))}
				</TableHeader>
				<TableBody>
					{table.getRowModel().rows.map((row) => (
						<TableRow key={row.id} className={rowClassName?.(row)}>
							{row.getVisibleCells().map((cell) => (
								<TableCell key={cell.id} className="px-4 py-2 text-center">
									{flexRender(cell.column.columnDef.cell, cell.getContext())}
								</TableCell>
							))}
						</TableRow>
					))}
				</TableBody>
			</UITable>
		</div>
	);
}
