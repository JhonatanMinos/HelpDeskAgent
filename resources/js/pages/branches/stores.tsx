import { Head, router, usePage } from '@inertiajs/react';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { useState } from 'react';
import { PaginationGeneric } from '@/components/paginationUI';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { StoreStatsCards } from '@/components/stores/store-stats-cards';
import { StoreToolbar } from '@/components/stores/store-toolbar';
import TableGeneric from '@/components/ui/tableG';
import { useCurrentTeam } from '@/hooks/use-current-team';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { index as branches, refreshStatus } from '@/routes/branches';
import { getStoreColumns } from '@/tables/store-columns';
import type { DashboardInvitation } from '@/types';

type PageProps = {
    dashboard: {
        total: number;
        online: number;
        regions: { north: number; south: number };
        states: number;
    };
    stores: Stores[];
    filters?: { search?: string };
    pendingInvitations?: DashboardInvitation[];
};

export default function StoreDashboard({ pendingInvitations = [] }: PageProps) {
    const currentTeam = useCurrentTeam();
    const { dashboard, stores, filters } = usePage<PageProps>().props;
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );
    const [refreshing, setRefreshing] = useState(false);

    const [search, setSearch] = useDebouncedSearch(
        branches({ current_team: currentTeam.slug }).url,
        filters?.search ?? '',
        ['stores', 'filters'],
    );

    const table = useReactTable({
        data: stores.data ?? [],
        columns: getStoreColumns(currentTeam.slug),
        getCoreRowModel: getCoreRowModel(),
    });

    const handleRefreshAll = () => {
        setRefreshing(true);

        router.post(
            refreshStatus({ current_team: currentTeam.slug }).url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: ['stores', 'dashboard', 'flash'],
                onFinish: () => setRefreshing(false),
            },
        );
    };

    return (
        <>
            <Head title="Sucursales" />

            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <StoreStatsCards
                    total={dashboard.total}
                    onlineCount={dashboard.online}
                    regions={dashboard.regions}
                />

                <StoreToolbar
                    search={search}
                    onSearchChange={setSearch}
                    onRefresh={handleRefreshAll}
                    refreshing={refreshing}
                />

                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <TableGeneric
                        table={table}
                        rowClassName={(row) =>
                            row.original.status === 'unknown' ||
                            row.original.status === 'offline'
                                ? 'bg-red-50 hover:bg-red-100 dark:bg-red-950/70'
                                : ''
                        }
                    />
                    <PaginationGeneric pagination={stores} />
                </div>
            </div>
        </>
    );
}

StoreDashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Sucursales',
            href: props.currentTeam ? branches(props.currentTeam.slug) : '/',
        },
    ],
});
