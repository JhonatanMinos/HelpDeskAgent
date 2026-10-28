import type { Row, Table } from '@tanstack/react-table';
import { flexRender } from '@tanstack/react-table';
import {
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
	Table as UITable,
} from '@/components/ui/table';

interface TableGenericProps<TData> {
	table: Table<TData>;
	rowClassName?: (row: Row<TData>) => string;
	emptyTitle?: string;
	emptyDescription?: string;
}

export default function TableGeneric<TData>({
	table,
	rowClassName,
	emptyTitle = 'No results found',
	emptyDescription = 'Try adjusting your search.',
}: TableGenericProps<TData>) {
	return (
		<div className="min-w-0 overflow-hidden rounded-lg">
			<UITable className="table-auto">
				<TableHeader className="bg-muted/60">
					{table.getHeaderGroups().map((headerGroup) => (
						<TableRow className="transition-colors hover:bg-white/5" key={headerGroup.id}>
							{headerGroup.headers.map((header) => (
								<TableHead
									key={header.id}
									scope="col"
									className="px-4 py-3 text-left text-xs uppercase tracking-wide text-muted-foreground"
								>
									{flexRender(header.column.columnDef.header, header.getContext())}
								</TableHead>
							))}
						</TableRow>
					))}
				</TableHeader>
				<TableBody>
					{table.getRowModel().rows.length === 0 && (
						<TableRow>
							<TableCell
								colSpan={table.getVisibleLeafColumns().length}
								className="h-48 px-6 text-center"
							>
								<p className="font-medium">{emptyTitle}</p>
								<p className="mt-2 text-sm text-muted-foreground">{emptyDescription}</p>
							</TableCell>
						</TableRow>
					)}
					{table.getRowModel().rows.map((row) => (
						<TableRow key={row.id} className={rowClassName?.(row)}>
							{row.getVisibleCells().map((cell) => (
								<TableCell key={cell.id} className="px-4 py-3 text-left">
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
