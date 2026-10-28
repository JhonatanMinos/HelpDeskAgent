import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Plus, SendHorizontal, Ticket as TicketIcon } from 'lucide-react';
import { PaginationGeneric } from '@/components/paginationUI';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useCurrentTeam } from '@/hooks/use-current-team';
import { show as showTicket, store as storeTicket, index as ticketsIndex } from '@/routes/tickets';

interface TicketSummary {
	id: number;
	title: string;
	status: string;
	priority: string;
	category: string;
	due_date: string | null;
	store: { id: number; name: string } | null;
	project: { id: number; name: string } | null;
	creator: { id: number; name: string } | null;
	assignee: { id: number; name: string } | null;
	created_at: string;
}

interface TeamMember {
	id: number;
	name: string;
}

interface PaginationData<T> {
	data: T[];
	prev_page_url: string | null;
	next_page_url: string | null;
}

interface PageProps extends InertiaPageProps {
	tickets: PaginationData<TicketSummary>;
	stores: Array<{ id: number; name: string }>;
	projects: Array<{ id: number; name: string }>;
	teamMembers: TeamMember[];
}

const STATUS_LABEL: Record<string, string> = {
	abierto: 'Abierto',
	en_proceso: 'En proceso',
	esperando_cliente: 'Esperando cliente',
	resuelto: 'Resuelto',
	cerrado: 'Cerrado',
};

const STATUS_CLASS: Record<string, string> = {
	abierto: 'bg-amber-500/15 text-amber-500',
	en_proceso: 'bg-blue-500/15 text-blue-500',
	esperando_cliente: 'bg-fuchsia-500/15 text-fuchsia-500',
	resuelto: 'bg-emerald-500/15 text-emerald-500',
	cerrado: 'bg-slate-500/15 text-slate-500',
};

const PRIORITY_LABEL: Record<string, string> = {
	baja: 'Baja',
	media: 'Media',
	alta: 'Alta',
};

const CATEGORY_LABEL: Record<string, string> = {
	red: 'Red',
	hardware: 'Hardware',
	software: 'Software',
	impresoras: 'Impresoras',
	usuarios: 'Usuarios',
	seguridad: 'Seguridad',
	configuracion: 'Configuración',
};

export default function TicketsIndex() {
	const currentTeam = useCurrentTeam();
	const { tickets, stores, projects, teamMembers } = usePage<PageProps>().props;

	const { data, setData, post, processing, errors, reset } = useForm({
		title: '',
		description: '',
		category: 'red',
		priority: 'media',
		store_id: '',
		project_id: '',
		assigned_to: '',
		due_date: '',
	});

	const submit = (event: React.FormEvent) => {
		event.preventDefault();

		post(storeTicket({ current_team: currentTeam.slug }).url, {
			onSuccess: () => {
				reset();
			},
		});
	};

	return (
		<>
			<Head title="Tickets" />

			<div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
				<div className="flex flex-col gap-5 rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/10 via-card to-card p-5 md:flex-row md:items-center md:justify-between md:p-6">
					<div>
						<p className="mb-2 text-xs font-semibold uppercase tracking-widest text-primary">
							Centro de soporte
						</p>
						<h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
							Tickets de soporte
						</h1>
						<p className="text-sm text-muted-foreground">
							Crea incidentes, asigna responsables y lleva el seguimiento de cada caso.
						</p>
					</div>

					<div className="rounded-xl border bg-background/70 p-4 text-xs text-muted-foreground">
						<p className="font-medium text-foreground">Flujo rápido</p>
						<p className="mt-1">Abierto → En proceso → Esperando cliente → Resuelto/Cerrado</p>
					</div>
				</div>

				<div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,0.7fr)]">
					<div className="min-w-0 rounded-2xl border bg-card p-5 shadow-sm md:p-6">
						<div className="mb-4 flex items-center gap-2">
							<TicketIcon className="size-4" />
							<h2 className="font-semibold">Tickets recientes</h2>
						</div>

						{tickets.data.length === 0 ? (
							<div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
								No hay tickets todavía. Crea el primero para empezar el seguimiento.
							</div>
						) : (
							<>
								<div className="space-y-3">
									{tickets.data.map((ticket) => (
										<Link
											key={ticket.id}
											href={
												showTicket({
													current_team: currentTeam.slug,
													ticket: ticket.id,
												}).url
											}
											className="group block rounded-xl border border-border/80 p-4 transition hover:border-primary/35 hover:bg-primary/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
										>
											<div className="flex flex-wrap items-start justify-between gap-3">
												<div>
													<h3 className="font-medium">{ticket.title}</h3>
													<p className="mt-1 text-sm text-muted-foreground">
														{ticket.category ? CATEGORY_LABEL[ticket.category] : 'Sin categoría'} ·{' '}
														{ticket.priority ? PRIORITY_LABEL[ticket.priority] : 'Sin prioridad'}
													</p>
												</div>
												<Badge
													variant="outline"
													className={
														STATUS_CLASS[ticket.status] ?? 'bg-slate-500/15 text-slate-500'
													}
												>
													{STATUS_LABEL[ticket.status] ?? ticket.status}
												</Badge>
											</div>

											<div className="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
												{ticket.store && (
													<span className="rounded-full bg-muted px-2 py-1">
														{ticket.store.name}
													</span>
												)}
												{ticket.project && (
													<span className="rounded-full bg-muted px-2 py-1">
														{ticket.project.name}
													</span>
												)}
												{ticket.assignee && (
													<span className="rounded-full bg-muted px-2 py-1">
														Asignado a {ticket.assignee.name}
													</span>
												)}
												{ticket.due_date && (
													<span className="rounded-full bg-muted px-2 py-1">
														Entrega: {new Date(ticket.due_date).toLocaleDateString('es-MX')}
													</span>
												)}
											</div>
										</Link>
									))}
								</div>

								<PaginationGeneric pagination={tickets} />
							</>
						)}
					</div>

					<div className="min-w-0 rounded-2xl border bg-card p-5 shadow-sm md:p-6">
						<div className="mb-4 flex items-center gap-2">
							<Plus className="size-4" />
							<h2 className="font-semibold">Crear ticket</h2>
						</div>
						<p className="mb-5 text-sm text-muted-foreground">
							Registra el incidente y conecta al equipo con la sucursal afectada.
						</p>

						<form onSubmit={submit} className="space-y-4">
							<div>
								<label htmlFor="ticket-create-title" className="text-sm font-medium">
									Título
								</label>
								<Input
									id="ticket-create-title"
									value={data.title}
									onChange={(event) => setData('title', event.target.value)}
									className="mt-1"
									placeholder="Ej. Problema con el posteo"
								/>
								{errors.title && <p className="mt-1 text-xs text-destructive">{errors.title}</p>}
							</div>

							<div>
								<label htmlFor="ticket-create-description" className="text-sm font-medium">
									Descripción
								</label>
								<Textarea
									id="ticket-create-description"
									value={data.description}
									onChange={(event) => setData('description', event.target.value)}
									rows={4}
									className="mt-1"
									placeholder="Contexto del problema que dice el error"
								/>
								{errors.description && (
									<p className="mt-1 text-xs text-destructive">{errors.description}</p>
								)}
							</div>

							<div className="grid gap-4 md:grid-cols-2">
								<div>
									<label htmlFor="ticket-create-category" className="text-sm font-medium">
										Categoría
									</label>
									<select
										id="ticket-create-category"
										value={data.category}
										onChange={(event) => setData('category', event.target.value)}
										className="mt-1 min-h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
									>
										<option value="red">Red</option>
										<option value="hardware">Hardware</option>
										<option value="software">Software</option>
										<option value="impresoras">Impresoras</option>
										<option value="usuarios">Usuarios</option>
										<option value="seguridad">Seguridad</option>
										<option value="configuracion">Configuración</option>
									</select>
								</div>

								<div>
									<label htmlFor="ticket-create-priority" className="text-sm font-medium">
										Prioridad
									</label>
									<select
										id="ticket-create-priority"
										value={data.priority}
										onChange={(event) => setData('priority', event.target.value)}
										className="mt-1 min-h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
									>
										<option value="baja">Baja</option>
										<option value="media">Media</option>
										<option value="alta">Alta</option>
									</select>
								</div>
							</div>

							<div className="grid gap-4 md:grid-cols-1">
								<div>
									<label htmlFor="ticket-create-store_id" className="text-sm font-medium">
										Sucursal
									</label>
									<select
										id="ticket-create-store_id"
										value={data.store_id}
										onChange={(event) => setData('store_id', event.target.value)}
										className="mt-1 min-h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
									>
										<option value="">Buscar Sucursal</option>
										{stores.map((store) => (
											<option key={store.id} value={store.id}>
												{store.name}
											</option>
										))}
									</select>
								</div>
							</div>

							<div className="grid gap-4 md:grid-cols-2">
								<div>
									<label htmlFor="ticket-create-assigned_to" className="text-sm font-medium">
										Asignar a
									</label>
									<select
										id="ticket-create-assigned_to"
										value={data.assigned_to}
										onChange={(event) => setData('assigned_to', event.target.value)}
										className="mt-1 min-h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none transition focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
									>
										<option value="">Sin asignar</option>
										{teamMembers.map((member) => (
											<option key={member.id} value={member.id}>
												{member.name}
											</option>
										))}
									</select>
								</div>

								<div>
									<label htmlFor="ticket-create-due_date" className="text-sm font-medium">
										Fecha límite
									</label>
									<Input
										id="ticket-create-due_date"
										type="date"
										value={data.due_date}
										onChange={(event) => setData('due_date', event.target.value)}
										className="mt-1"
									/>
								</div>
							</div>

							<Button type="submit" className="w-full" disabled={processing}>
								Crear ticket <SendHorizontal />
							</Button>
						</form>
					</div>
				</div>
			</div>
		</>
	);
}

TicketsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
	breadcrumbs: [
		{
			title: 'Tickets',
			href: props.currentTeam ? ticketsIndex(props.currentTeam.slug) : '/',
		},
	],
});
