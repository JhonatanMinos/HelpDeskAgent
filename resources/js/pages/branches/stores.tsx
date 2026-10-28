import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/react';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { Building2 } from 'lucide-react';
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
import type { DashboardInvitation, Store } from '@/types';

type StorePaginator = {
	data: Store[];
	prev_page_url: string | null;
	next_page_url: string | null;
};

type PageProps = InertiaPageProps & {
	dashboard: {
		total: number;
		online: number;
		company: Array<{ company: number; total: number }>;
		states: number;
	};
	stores: StorePaginator;
	filters?: { search?: string };
	pendingInvitations?: DashboardInvitation[];
};

export default function StoreDashboard({ pendingInvitations = [] }: PageProps) {
	const currentTeam = useCurrentTeam();
	const { dashboard, stores, filters } = usePage<PageProps>().props;
	const [showInvitations, setShowInvitations] = useState(pendingInvitations.length > 0);
	const [refreshing, setRefreshing] = useState(false);

	const [search, setSearch] = useDebouncedSearch(
		branches({ current_team: currentTeam.slug }).url,
		filters?.search ?? '',
		['stores', 'filters']
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
			}
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

			<div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
				<header className="flex flex-wrap items-center justify-between gap-3">
					<div>
						<p className="mb-1 text-xs font-semibold uppercase tracking-widest text-primary">
							Operación de sucursales
						</p>
						<h1 className="text-2xl font-semibold tracking-tight md:text-3xl">Sucursales</h1>
						<p className="mt-2 text-sm text-muted-foreground">
							Consulta el estado registrado y accede al diagnóstico de cada ubicación.
						</p>
					</div>
					<Building2 className="hidden size-9 text-primary/40 sm:block" />
				</header>
				<StoreStatsCards
					total={dashboard.total}
					onlineCount={dashboard.online}
					companys={dashboard.company}
				/>

				<section className="rounded-xl border bg-card p-4">
					<StoreToolbar
						search={search}
						onSearchChange={setSearch}
						onRefresh={handleRefreshAll}
						refreshing={refreshing}
					/>
				</section>

				<div className="min-w-0 overflow-hidden rounded-xl border bg-card">
					<TableGeneric
						table={table}
						emptyTitle={
							search ? 'No hay sucursales que coincidan' : 'No hay sucursales registradas'
						}
						emptyDescription={
							search
								? 'Prueba con otro nombre o código de sucursal.'
								: 'Las sucursales de tu equipo aparecerán aquí.'
						}
						rowClassName={(row) =>
							row.original.status === 'offline'
								? 'bg-rose-500/5 hover:bg-rose-500/10'
								: row.original.status === 'unknown'
									? 'bg-amber-500/5 hover:bg-amber-500/10'
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
