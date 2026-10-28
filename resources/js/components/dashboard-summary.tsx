import { Link } from '@inertiajs/react';
import {
	ArrowRight,
	Building2,
	CircleHelp,
	Clock3,
	Radio,
	Ticket,
	Wifi,
	WifiOff,
} from 'lucide-react';
import { index as branches, show as showBranch } from '@/routes/branches';
import { show as showTicket, index as tickets } from '@/routes/tickets';
import type { DashboardOverview } from '@/types/dashboard';

const ticketLabels = {
	abierto: 'Abiertos',
	en_proceso: 'En proceso',
	esperando_cliente: 'Esperando al cliente',
	resuelto: 'Resueltos',
	cerrado: 'Cerrados',
};
const activityLabels: Record<string, string> = {
	created: 'Ticket creado',
	updated: 'Ticket actualizado',
	comment: 'Nuevo seguimiento',
};

function Timestamp({ value }: { value: string | null }) {
	if (!value) return <span>Sin comprobaciones</span>;
	return (
		<time dateTime={value}>
			{new Date(value).toLocaleString('es-MX', {
				timeZone: 'UTC',
				dateStyle: 'short',
				timeStyle: 'short',
			})}{' '}
			UTC
		</time>
	);
}

export function DashboardSummary({
	overview,
	teamSlug,
}: {
	overview: DashboardOverview;
	teamSlug: string;
}) {
	const { branches: summary, attention, activity } = overview;
	const percentage = summary.total ? Math.round((summary.online / summary.total) * 100) : null;
	const states = [
		{
			key: 'online' as const,
			label: 'En línea',
			icon: Wifi,
			color: 'text-emerald-600 dark:text-emerald-400',
			bar: 'bg-emerald-400',
			description: 'VPN y servidor disponibles',
		},
		{
			key: 'offline' as const,
			label: 'Sin conexión',
			icon: WifiOff,
			color: 'text-rose-600 dark:text-rose-400',
			bar: 'bg-rose-400',
			description: 'Requieren atención',
		},
		{
			key: 'unknown' as const,
			label: 'Sin verificar',
			icon: CircleHelp,
			color: 'text-amber-600 dark:text-amber-400',
			bar: 'bg-amber-300',
			description: 'Estado desconocido',
		},
	];

	return (
		<div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
			<header className="flex flex-wrap items-end justify-between gap-3">
				<div>
					<p className="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
						Resumen operativo
					</p>
					<h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
						El pulso de tus sucursales
					</h1>
				</div>
				<span className="flex items-center gap-2 text-xs text-muted-foreground">
					<Clock3 className="size-3.5" />
					Datos de la última comprobación registrada
				</span>
			</header>

			<section
				aria-labelledby="branches-heading"
				className="relative overflow-hidden rounded-2xl bg-slate-950 p-6 text-white md:p-8"
			>
				<div
					aria-hidden="true"
					className="pointer-events-none absolute -right-20 -top-32 size-96 rounded-full bg-emerald-500/15 blur-3xl"
				/>
				<div className="relative grid items-center gap-8 md:grid-cols-[1fr_auto]">
					<div>
						<div className="mb-4 flex items-center gap-2 text-sm text-emerald-300">
							<Radio className="size-4" />
							<h2 id="branches-heading">Sucursales · Estado de conexión</h2>
						</div>
						<p className="text-5xl font-semibold tracking-tight md:text-6xl">
							{summary.total}
							<span className="ml-3 text-base font-normal text-slate-400">registradas</span>
						</p>
						<p className="mt-3 max-w-lg text-sm leading-relaxed text-slate-300">
							{summary.total === 0
								? 'Sin sucursales registradas. Agrega tu primera sucursal para comenzar.'
								: `${summary.offline} sin conexión y ${summary.unknown} sin verificar. Consulta el detalle antes de realizar un diagnóstico.`}
						</p>
						<Link
							href={branches(teamSlug)}
							className="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-medium text-slate-950 transition hover:bg-emerald-100 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-300"
						>
							Ver sucursales
							<ArrowRight className="size-4" />
						</Link>
					</div>
					<div className="flex flex-col items-center gap-3 md:px-8">
						<div className="relative grid size-40 place-items-center">
							<svg
								viewBox="0 0 120 120"
								aria-hidden="true"
								className="absolute inset-0 size-full -rotate-90"
							>
								<circle
									cx="60"
									cy="60"
									r="52"
									fill="none"
									stroke="currentColor"
									strokeWidth="7"
									className="text-slate-800"
								/>
								<circle
									cx="60"
									cy="60"
									r="52"
									fill="none"
									stroke="currentColor"
									strokeWidth="7"
									pathLength="100"
									strokeDasharray={`${percentage ?? 0} 100`}
									strokeLinecap="round"
									className="text-emerald-400"
								/>
							</svg>
							<div className="text-center">
								<p className="text-3xl font-semibold">
									{percentage === null ? 'Sin datos' : `${percentage}%`}
								</p>
								<p className="mt-1 text-xs text-slate-400">en línea</p>
							</div>
						</div>
						<p className="text-xs text-slate-400">Estado registrado, no disponibilidad histórica</p>
					</div>
				</div>
				{summary.total > 0 && (
					<div
						className="relative mt-7 flex h-1.5 overflow-hidden rounded-full bg-slate-800"
						aria-hidden="true"
					>
						{states.map(({ key, bar }) => (
							<div
								key={key}
								className={bar}
								style={{ width: `${(summary[key] / summary.total) * 100}%` }}
							/>
						))}
					</div>
				)}
			</section>

			<div className="grid gap-4 sm:grid-cols-3">
				{states.map(({ key, label, icon: Icon, color, description }) => (
					<div key={key} className="flex items-start justify-between rounded-xl border bg-card p-5">
						<div>
							<p className="text-sm text-muted-foreground">{label}</p>
							<p className={`my-2 text-3xl font-semibold tabular-nums ${color}`}>{summary[key]}</p>
							<p className="text-xs text-muted-foreground">{description}</p>
						</div>
						<Icon className={`size-5 ${color}`} />
					</div>
				))}
			</div>

			<div className="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
				<section
					aria-labelledby="attention-heading"
					className="min-w-0 overflow-hidden rounded-xl border bg-card"
				>
					<div className="flex items-center justify-between gap-3 border-b p-5">
						<div>
							<h2 id="attention-heading" className="font-semibold">
								Sucursales que requieren atención
							</h2>
							<p className="mt-1 text-xs text-muted-foreground">
								Sin conexión primero · Hasta 6 sucursales
							</p>
						</div>
						<Building2 className="size-5 shrink-0 text-muted-foreground" />
					</div>
					{attention.length ? (
						<ul className="divide-y">
							{attention.map((store) => (
								<li key={store.id}>
									<Link
										href={showBranch({ current_team: teamSlug, branch: store.id })}
										className="flex flex-wrap items-center justify-between gap-3 p-5 transition hover:bg-muted/50 focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-primary"
									>
										<div className="min-w-0 flex-1">
											<div className="flex items-center gap-2">
												<span className="shrink-0 rounded bg-muted px-1.5 py-0.5 font-mono text-xs text-muted-foreground">
													{store.code}
												</span>
												<h3 className="truncate text-sm font-medium">{store.name}</h3>
											</div>
											<p className="mt-1 text-xs text-muted-foreground">
												{store.city}, {store.state}
											</p>
											<p className="mt-2 text-xs text-muted-foreground">
												Última comprobación: <Timestamp value={store.lastCheckedAt} />
											</p>
										</div>
										<span
											className={`rounded-full px-2.5 py-1 text-xs font-medium ${store.status === 'offline' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300' : 'bg-amber-500/10 text-amber-700 dark:text-amber-300'}`}
										>
											{store.status === 'offline' ? 'Sin conexión' : 'Sin verificar'}
										</span>
										<ArrowRight className="size-4 text-muted-foreground" />
									</Link>
								</li>
							))}
						</ul>
					) : (
						<div className="px-5 py-12 text-center">
							<Wifi className="mx-auto mb-3 size-7 text-muted-foreground" />
							<p className="text-sm font-medium">
								{summary.total
									? 'Sin sucursales pendientes de atención'
									: 'Sin sucursales registradas'}
							</p>
							<p className="mt-1 text-xs text-muted-foreground">
								{summary.total
									? 'Según el último estado registrado.'
									: 'Las sucursales aparecerán aquí cuando estén registradas.'}
							</p>
						</div>
					)}
					<Link
						href={branches(teamSlug)}
						className="flex items-center justify-center gap-2 border-t p-4 text-sm font-medium text-primary hover:underline"
					>
						Ver todas las sucursales
						<ArrowRight className="size-4" />
					</Link>
				</section>

				<section aria-labelledby="tickets-heading" className="rounded-xl border bg-card p-5">
					<div className="mb-5 flex items-center justify-between">
						<h2 id="tickets-heading" className="font-semibold">
							Tickets del equipo
						</h2>
						<Ticket className="size-4 text-muted-foreground" />
					</div>
					<dl className="space-y-4">
						{(Object.keys(ticketLabels) as (keyof typeof ticketLabels)[]).map((status) => (
							<div key={status} className="flex items-center justify-between gap-3 text-sm">
								<dt className="text-muted-foreground">{ticketLabels[status]}</dt>
								<dd className="font-semibold tabular-nums">{overview.tickets[status]}</dd>
							</div>
						))}
					</dl>
					<Link
						href={tickets(teamSlug)}
						className="mt-6 flex items-center gap-2 border-t pt-4 text-sm font-medium text-primary hover:underline"
					>
						Ver tickets
						<ArrowRight className="size-4" />
					</Link>
				</section>
			</div>

			<section aria-labelledby="activity-heading" className="rounded-xl border bg-card p-5">
				<h2 id="activity-heading" className="mb-4 font-semibold">
					Actividad reciente
				</h2>
				{activity.length ? (
					<ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
						{activity.map((event) => (
							<li key={event.id} className="min-w-0 border-l-2 border-primary/30 pl-3">
								<p className="text-xs text-muted-foreground">
									{activityLabels[event.type] ?? 'Actividad de ticket'}
								</p>
								<Link
									href={showTicket({ current_team: teamSlug, ticket: event.ticketId })}
									className="mt-1 block truncate text-sm font-medium hover:underline"
								>
									{event.ticketTitle}
								</Link>
								{event.createdAt && (
									<p className="mt-1 text-xs text-muted-foreground">
										<Timestamp value={event.createdAt} />
									</p>
								)}
							</li>
						))}
					</ul>
				) : (
					<p className="py-4 text-sm text-muted-foreground">
						Sin actividad reciente. Los movimientos de tickets aparecerán aquí.
					</p>
				)}
			</section>
		</div>
	);
}
