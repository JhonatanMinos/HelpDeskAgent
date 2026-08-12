import { Link } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { EllipsisVertical, Eye, Loader2, Radar } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useStatePing } from '@/hooks/use-store-ping';
import { show as showBranch } from '@/routes/branches';
import type { Stores } from '@/types';

export function getStoreColumns(currentTeamSlug: string): ColumnDef<T>[] {
    return [
        {
            accessorKey: 'store_code',
            header: 'Codigo',
            cell: ({ row }) => (
                <Badge variant="secondary">{row.getValue('store_code')}</Badge>
            ),
        },
        {
            accessorKey: 'name',
            header: 'name',
        },
        {
            accessorKey: 'base_ip',
            header: 'Base IP',
            cell: ({ row }) => (
                <div className="text-muted-foreground">
                    {row.getValue('base_ip')}
                </div>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Estatus',
            cell: ({ row }) => {
                const value = row.getValue('status');

                const colors = {
                    online: 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300',
                    offline:
                        'bg-gray-50 text-gray-700 dark:bg-gray-950 dark:text-gray-300',
                    unknown:
                        'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
                };

                const colorsDot = {
                    online: 'bg-green-600',
                    offline: 'bg-gray-600',
                    unknown: 'bg-red-600',
                };

                return (
                    <Badge
                        variant="secondary"
                        className={`px-2.5 capitalize ${colors[value] ?? ''}`}
                    >
                        <span
                            className={`h-2 w-2 rounded-full ${colorsDot[value]}`}
                        />
                        {value}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'state',
            header: 'Estado',
            cell: ({ row }) => (
                <Badge className="bg-blue-950 text-gray-300">
                    {row.getValue('state')}
                </Badge>
            ),
        },
        {
            id: 'actions',
            cell: ({ row }) => (
                <StoreRowActions
                    store={row.original}
                    currentTeamSlug={currentTeamSlug}
                />
            ),
        },
    ];
}

function StoreRowActions({
    store,
    currentTeamSlug,
}: {
    store: Store;
    currentTeamSlug: string;
}) {
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
                        <span className="sr-only">Open menu</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-32">
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
                        {pinging ? (
                            <Loader2 className="animate-spin" size={14} />
                        ) : (
                            <Radar size={14} />
                        )}
                        {pinging ? 'Pingueando...' : 'Ping ahora'}
                    </DropdownMenuItem>
                    <DropdownMenuItem>Favorite</DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem variant="destructive">
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
