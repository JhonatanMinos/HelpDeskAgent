import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { EllipsisVertical, Eye, Loader2, Radar } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useStatePing } from '@/hooks/use-store-ping';
import { show as showBranch } from '@/routes/branches';
import type { Store } from '@/types';

export function getStoreColumns(currentTeamSlug: string): ColumnDef<Store>[] {
	return [
		{
			accessorKey: 'store_code',
			header: 'Código',
			cell: ({ row }) => <Badge variant="secondary">{row.getValue('store_code')}</Badge>,
		},
		{
			accessorKey: 'name',
			header: 'Sucursal',
		},
		{
			accessorKey: 'base_ip',
			header: 'IP base',
			cell: ({ row }) => <div className="text-muted-foreground">{row.getValue('base_ip')}</div>,
		},
		{
			accessorKey: 'status',
			header: 'Estatus',
			cell: ({ row }) => {
				const value = row.getValue<Store['status']>('status');

				const colors = {
					online: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
					offline: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
					unknown: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
				};

				const colorsDot = {
					online: 'bg-emerald-500',
					offline: 'bg-rose-500',
					unknown: 'bg-amber-500',
				};

				return (
					<Badge
						title={value}
						variant="secondary"
						className={`gap-1.5 px-2.5 ${colors[value] ?? ''}`}
					>
						<span className={`h-2 w-2 rounded-full ${colorsDot[value]}`} />
						{{ online: 'En línea', offline: 'Sin conexión', unknown: 'Sin verificar' }[value]}
					</Badge>
				);
			},
		},
		{
			accessorKey: 'state',
			header: 'Estado',
			cell: ({ row }) => (
				<Badge variant="outline" className="font-normal text-muted-foreground">
					{row.getValue('state')}
				</Badge>
			),
		},
		{
			id: 'actions',
			cell: ({ row }) => <StoreRowActions store={row.original} currentTeamSlug={currentTeamSlug} />,
		},
	];
}

function StoreRowActions({ store, currentTeamSlug }: { store: Store; currentTeamSlug: string }) {
	const { pingStore, isPinging } = useStatePing(currentTeamSlug);
	const pinging = isPinging(store.id);
	return (
		<div className="flex justify-center gap-2">
			<DropdownMenu>
				<DropdownMenuTrigger asChild>
					<Button
						variant="ghost"
						className="flex size-8 text-muted-foreground data-[state=open]:bg-muted"
						size="icon"
					>
						<EllipsisVertical />
						<span className="sr-only">Acciones de sucursal</span>
					</Button>
				</DropdownMenuTrigger>
				<DropdownMenuContent align="end" className="w-48">
					<DropdownMenuItem asChild>
						<Link
							href={
								showBranch({
									current_team: currentTeamSlug,
									branch: store.id,
								}).url
							}
						>
							<Eye size={14} />
							Ver detalle
						</Link>
					</DropdownMenuItem>{' '}
					<DropdownMenuItem
						onSelect={(e) => {
							e.preventDefault(); // evita que el menú se cierre antes del click
							pingStore(store.id);
						}}
						disabled={pinging}
					>
						{pinging ? <Loader2 className="animate-spin" size={14} /> : <Radar size={14} />}
						{pinging ? 'Comprobando...' : 'Comprobar conexión'}
					</DropdownMenuItem>
				</DropdownMenuContent>
			</DropdownMenu>
		</div>
	);
}
