import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, MessageSquareText, Ticket as TicketIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useCurrentTeam } from '@/hooks/use-current-team';
import { index as ticketsIndex, update as updateTicket } from '@/routes/tickets';
import comments from '@/routes/tickets/comments';

interface TicketComment {
    id: number;
    body: string;
    created_at: string;
    user: { id: number; name: string };
}

interface TicketActivity {
    id: number;
    type: string;
    field: string | null;
    old_value: string | null;
    new_value: string | null;
    note: string | null;
    created_at: string;
    user: { id: number; name: string };
}

interface TicketDetails {
    id: number;
    title: string;
    description: string | null;
    status: string;
    priority: string;
    category: string;
    due_date: string | null;
    resolved_at: string | null;
    created_at: string;
    store: { id: number; name: string } | null;
    project: { id: number; name: string } | null;
    creator: { id: number; name: string } | null;
    assignee: { id: number; name: string } | null;
    comments: TicketComment[];
    activities: TicketActivity[];
}

interface TeamMember {
    id: number;
    name: string;
}

interface PageProps {
    ticket: TicketDetails;
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

const ACTIVITY_TYPE_LABEL: Record<string, string> = {
    created: 'Ticket creado',
    updated: 'Cambio de ticket',
    comment: 'Comentario agregado',
};

const ACTIVITY_FIELD_LABEL: Record<string, string> = {
    title: 'Título',
    description: 'Descripción',
    category: 'Categoría',
    priority: 'Prioridad',
    status: 'Estado',
    store_id: 'Sucursal',
    project_id: 'Proyecto',
    assigned_to: 'Asignado a',
    due_date: 'Fecha límite',
    resolved_at: 'Resuelto en',
};

export default function TicketShow() {
    const currentTeam = useCurrentTeam();
    const { ticket, stores, projects, teamMembers } = usePage<PageProps>().props;

    const { data, setData, patch, processing, errors } = useForm({
        title: ticket.title,
        description: ticket.description ?? '',
        category: ticket.category,
        priority: ticket.priority,
        status: ticket.status,
        store_id: ticket.store?.id?.toString() ?? '',
        project_id: ticket.project?.id?.toString() ?? '',
        assigned_to: ticket.assignee?.id?.toString() ?? '',
        due_date: ticket.due_date ?? '',
    });

    const commentForm = useForm({ body: '' });

    const submitUpdate = (event: React.FormEvent) => {
        event.preventDefault();

        patch(updateTicket({ current_team: currentTeam.slug, ticket: ticket.id }).url, {
            preserveScroll: true,
        });
    };

    const submitComment = (event: React.FormEvent) => {
        event.preventDefault();

        commentForm.post(comments.store({ current_team: currentTeam.slug, ticket: ticket.id }).url, {
            preserveScroll: true,
            onSuccess: () => commentForm.reset('body'),
        });
    };

    return (
        <>
            <Head title={`Ticket #${ticket.id}`} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center gap-2">
                    <Link href={ticketsIndex(currentTeam.slug).url} className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="size-4" />
                        Volver a tickets
                    </Link>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div className="space-y-6">
                        <div className="rounded-xl border bg-card p-4 shadow-sm">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">#{ticket.id}</p>
                                    <h1 className="text-xl font-semibold">{ticket.title}</h1>
                                </div>
                                <Badge variant="outline" className={STATUS_CLASS[ticket.status] ?? 'bg-slate-500/15 text-slate-500'}>
                                    {STATUS_LABEL[ticket.status] ?? ticket.status}
                                </Badge>
                            </div>

                            <div className="mt-4 flex flex-wrap gap-2 text-sm text-muted-foreground">
                                <span className="rounded-full bg-muted px-2 py-1">{CATEGORY_LABEL[ticket.category] ?? ticket.category}</span>
                                <span className="rounded-full bg-muted px-2 py-1">{PRIORITY_LABEL[ticket.priority] ?? ticket.priority}</span>
                                {ticket.store && <span className="rounded-full bg-muted px-2 py-1">Sucursal: {ticket.store.name}</span>}
                                {ticket.project && <span className="rounded-full bg-muted px-2 py-1">Proyecto: {ticket.project.name}</span>}
                                {ticket.assignee && <span className="rounded-full bg-muted px-2 py-1">Asignado a {ticket.assignee.name}</span>}
                            </div>

                            <div className="mt-4 rounded-lg border bg-muted/40 p-4">
                                <p className="text-sm leading-6 whitespace-pre-wrap">{ticket.description || 'Sin descripción'}</p>
                            </div>
                        </div>

                        <div className="rounded-xl border bg-card p-4 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <MessageSquareText className="size-4" />
                                <h2 className="font-semibold">Seguimiento</h2>
                            </div>

                            <form onSubmit={submitComment} className="space-y-3">
                                <Textarea value={commentForm.data.body} onChange={(event) => commentForm.setData('body', event.target.value)} rows={4} placeholder="Escribe una actualización o respuesta para el cliente..." />
                                {commentForm.errors.body && <p className="text-xs text-red-500">{commentForm.errors.body}</p>}
                                <Button type="submit" disabled={commentForm.processing}>
                                    Agregar seguimiento
                                </Button>
                            </form>

                            <div className="mt-6 space-y-3">
                                {ticket.comments.length === 0 ? (
                                    <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                        Aún no hay seguimientos. Añade el primer comentario para registrar el avance.
                                    </div>
                                ) : (
                                    ticket.comments.map((comment) => (
                                        <div key={comment.id} className="rounded-lg border p-3">
                                            <div className="mb-2 flex items-center justify-between text-sm">
                                                <span className="font-medium">{comment.user.name}</span>
                                                <span className="text-muted-foreground">{new Date(comment.created_at).toLocaleString('es-MX')}</span>
                                            </div>
                                            <p className="text-sm leading-6 whitespace-pre-wrap">{comment.body}</p>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>

                       <div className="rounded-xl border bg-card p-4 shadow-sm">
                           <div className="mb-4 flex items-center gap-2">
                               <TicketIcon className="size-4" />
                               <h2 className="font-semibold">Historial de actividad</h2>
                           </div>

                           <div className="space-y-3">
                               {ticket.activities.length === 0 ? (
                                   <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                       No hay actividad registrada aún.
                                   </div>
                               ) : (
                                   ticket.activities.map((activity) => (
                                       <div key={activity.id} className="rounded-lg border p-3">
                                           <div className="mb-2 flex items-center justify-between text-sm text-muted-foreground">
                                               <span>{activity.user.name}</span>
                                               <span>{new Date(activity.created_at).toLocaleString('es-MX')}</span>
                                           </div>
                                           <div className="mb-2 text-sm">
                                               <p className="font-medium">{ACTIVITY_TYPE_LABEL[activity.type] ?? activity.type}</p>
                                               {activity.field && (
                                                   <p className="text-xs text-muted-foreground">{ACTIVITY_FIELD_LABEL[activity.field] ?? activity.field}</p>
                                               )}
                                           </div>
                                           {activity.note && <p className="text-sm leading-6 whitespace-pre-wrap">{activity.note}</p>}
                                           {activity.old_value !== null || activity.new_value !== null ? (
                                               <div className="mt-2 rounded-lg bg-muted/40 p-3 text-sm">
                                                   <p className="text-xs text-muted-foreground">Desde</p>
                                                   <p>{activity.old_value ?? '—'}</p>
                                                   <p className="text-xs text-muted-foreground mt-2">Hasta</p>
                                                   <p>{activity.new_value ?? '—'}</p>
                                               </div>
                                           ) : null}
                                       </div>
                                   ))
                               )}
                           </div>
                       </div>

                    </div>
 
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <div className="mb-4 flex items-center gap-2">
                            <TicketIcon className="size-4" />
                            <h2 className="font-semibold">Actualizar ticket</h2>
                        </div>

                        <form onSubmit={submitUpdate} className="space-y-4">
                            <div>
                                <label className="text-sm font-medium">Título</label>
                                <Input value={data.title} onChange={(event) => setData('title', event.target.value)} className="mt-1" />
                                {errors.title && <p className="mt-1 text-xs text-red-500">{errors.title}</p>}
                            </div>

                            <div>
                                <label className="text-sm font-medium">Descripción</label>
                                <Textarea value={data.description} onChange={(event) => setData('description', event.target.value)} rows={4} className="mt-1" />
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">Estado</label>
                                    <select value={data.status} onChange={(event) => setData('status', event.target.value)} className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm">
                                        <option value="abierto">Abierto</option>
                                        <option value="en_proceso">En proceso</option>
                                        <option value="esperando_cliente">Esperando cliente</option>
                                        <option value="resuelto">Resuelto</option>
                                        <option value="cerrado">Cerrado</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="text-sm font-medium">Prioridad</label>
                                    <select value={data.priority} onChange={(event) => setData('priority', event.target.value)} className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm">
                                        <option value="baja">Baja</option>
                                        <option value="media">Media</option>
                                        <option value="alta">Alta</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="text-sm font-medium">Categoría</label>
                                <select value={data.category} onChange={(event) => setData('category', event.target.value)} className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm">
                                    <option value="red">Red</option>
                                    <option value="hardware">Hardware</option>
                                    <option value="software">Software</option>
                                    <option value="impresoras">Impresoras</option>
                                    <option value="usuarios">Usuarios</option>
                                    <option value="seguridad">Seguridad</option>
                                    <option value="configuracion">Configuración</option>
                                </select>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">Sucursal</label>
                                    <select value={data.store_id} onChange={(event) => setData('store_id', event.target.value)} className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm">
                                        <option value="">Sin sucursal</option>
                                        {stores.map((store) => (
                                            <option key={store.id} value={store.id}>{store.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="text-sm font-medium">Proyecto</label>
                                    <select value={data.project_id} onChange={(event) => setData('project_id', event.target.value)} className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm">
                                        <option value="">Sin proyecto</option>
                                        {projects.map((project) => (
                                            <option key={project.id} value={project.id}>{project.name}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="text-sm font-medium">Asignar a</label>
                                    <select value={data.assigned_to} onChange={(event) => setData('assigned_to', event.target.value)} className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm">
                                        <option value="">Sin asignar</option>
                                        {teamMembers.map((member) => (
                                            <option key={member.id} value={member.id}>{member.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="text-sm font-medium">Fecha límite</label>
                                    <Input type="date" value={data.due_date} onChange={(event) => setData('due_date', event.target.value)} className="mt-1" />
                                </div>
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                Guardar cambios
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}

TicketShow.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Tickets',
            href: props.currentTeam ? ticketsIndex(props.currentTeam.slug) : '/',
        },
    ],
});
