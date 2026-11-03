import { useForm } from '@inertiajs/react';
import { Archive, ArchiveRestore, MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import { useId, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

export interface EditableTask {
	id: number;
	title: string;
	description: string | null;
	priority: 'baja' | 'media' | 'alta';
	assigned_to: number | null;
	limit_date: string | null;
	archived_at: string | null;
	assignee?: { name: string } | null;
	column?: { name: string } | null;
}

export interface TaskMember {
	id: number;
	name: string;
}

export function TaskActions({
	task,
	teamSlug,
	members = [],
	archivingAvailable = true,
}: {
	task: EditableTask;
	teamSlug: string;
	members?: TaskMember[];
	archivingAvailable?: boolean;
}) {
	const id = useId();
	const [action, setAction] = useState<'edit' | 'archive' | 'restore' | 'delete' | null>(null);
	const edit = useForm({
		title: '',
		description: '',
		priority: 'media' as EditableTask['priority'],
		due_date: '',
		assigned_to: '',
	});
	const mutation = useForm<Record<string, never>>({});
	const processing = edit.processing || mutation.processing;
	const url = `/${encodeURIComponent(teamSlug)}/dev/tasks/${task.id}`;
	const onSuccess = () => setAction(null);
	const openEdit = () => {
		if (processing) return;
		edit.setData({
			title: task.title,
			description: task.description ?? '',
			priority: task.priority,
			due_date: task.limit_date?.slice(0, 10) ?? '',
			assigned_to: task.assigned_to == null ? '' : String(task.assigned_to),
		});
		edit.clearErrors();
		setAction('edit');
	};
	const labels = {
		edit: 'Editar tarea',
		archive: 'Archivar tarea',
		restore: 'Restaurar tarea',
		delete: 'Eliminar tarea',
	};

	return (
		<Dialog open={action !== null} onOpenChange={(open) => !open && !processing && setAction(null)}>
			<DropdownMenu>
				<DropdownMenuTrigger asChild>
					<Button
						type="button"
						variant="ghost"
						size="icon"
						className="size-8"
						disabled={processing}
						aria-label={`Acciones de ${task.title}`}
						onPointerDown={(event) => event.stopPropagation()}
					>
						<MoreHorizontal aria-hidden="true" />
					</Button>
				</DropdownMenuTrigger>
				<DropdownMenuContent align="end">
					<DropdownMenuItem
						disabled={processing}
						aria-label={`Editar ${task.title}`}
						onSelect={openEdit}
					>
						<Pencil aria-hidden="true" /> Editar
					</DropdownMenuItem>
					<DropdownMenuItem
						disabled={processing || !archivingAvailable}
						aria-label={`${task.archived_at ? 'Restaurar' : 'Archivar'} ${task.title}`}
						onSelect={() => {
							if (!processing && archivingAvailable) {
								mutation.clearErrors();
								setAction(task.archived_at ? 'restore' : 'archive');
							}
						}}
					>
						{task.archived_at ? (
							<ArchiveRestore aria-hidden="true" />
						) : (
							<Archive aria-hidden="true" />
						)}
						{task.archived_at ? 'Restaurar' : 'Archivar'}
					</DropdownMenuItem>
					<DropdownMenuItem
						variant="destructive"
						disabled={processing}
						aria-label={`Eliminar ${task.title}`}
						onSelect={() => {
							if (!processing) {
								mutation.clearErrors();
								setAction('delete');
							}
						}}
					>
						<Trash2 aria-hidden="true" /> Eliminar
					</DropdownMenuItem>
				</DropdownMenuContent>
			</DropdownMenu>
			<DialogContent
				className="max-h-[85dvh] overflow-y-auto sm:max-w-lg"
				showCloseButton={!processing}
			>
				<DialogHeader>
					<DialogTitle>{action ? labels[action] : 'Acciones de tarea'}</DialogTitle>
					<DialogDescription>
						{action === 'delete'
							? `Se eliminará “${task.title}”. Esta acción no se puede deshacer.`
							: action === 'archive'
								? 'La tarea se ocultará del tablero. Se puede restaurar desde las tareas archivadas.'
								: action === 'restore'
									? 'La tarea volverá a su columna original del tablero.'
									: task.title}
					</DialogDescription>
				</DialogHeader>
				{action !== null && (
					<form
						className="space-y-4"
						onSubmit={(event) => {
							event.preventDefault();
							if (processing) return;
							const options = { preserveScroll: true, onSuccess };
							if (action === 'edit') edit.patch(url, options);
							else if (action === 'delete') mutation.delete(url, options);
							else mutation.patch(`${url}/${action}`, options);
						}}
					>
						{action === 'edit' && (
							<>
								<div className="space-y-1">
									<label htmlFor={`${id}-title`} className="text-sm font-medium">
										Título
									</label>
									<Input
										id={`${id}-title`}
										required
										maxLength={255}
										value={edit.data.title}
										disabled={processing}
										onChange={(event) => edit.setData('title', event.target.value)}
									/>
								</div>
								<div className="space-y-1">
									<label htmlFor={`${id}-description`} className="text-sm font-medium">
										Descripción
									</label>
									<Textarea
										id={`${id}-description`}
										rows={3}
										value={edit.data.description}
										disabled={processing}
										onChange={(event) => edit.setData('description', event.target.value)}
									/>
								</div>
								<div className="grid gap-4 sm:grid-cols-2">
									<div className="space-y-1">
										<label htmlFor={`${id}-priority`} className="text-sm font-medium">
											Prioridad
										</label>
										<select
											id={`${id}-priority`}
											className="h-10 w-full rounded-md border bg-background px-3 text-sm"
											value={edit.data.priority}
											disabled={processing}
											onChange={(event) =>
												edit.setData('priority', event.target.value as EditableTask['priority'])
											}
										>
											<option value="baja">Baja</option>
											<option value="media">Media</option>
											<option value="alta">Alta</option>
										</select>
									</div>
									<div className="space-y-1">
										<label htmlFor={`${id}-due-date`} className="text-sm font-medium">
											Fecha límite
										</label>
										<Input
											id={`${id}-due-date`}
											type="date"
											value={edit.data.due_date}
											disabled={processing}
											onChange={(event) => edit.setData('due_date', event.target.value)}
										/>
									</div>
								</div>
								<div className="space-y-1">
									<label htmlFor={`${id}-assignee`} className="text-sm font-medium">
										Asignar a
									</label>
									<select
										id={`${id}-assignee`}
										className="h-10 w-full rounded-md border bg-background px-3 text-sm"
										value={edit.data.assigned_to}
										disabled={processing}
										onChange={(event) => edit.setData('assigned_to', event.target.value)}
									>
										<option value="">Sin asignar</option>
										{task.assigned_to != null &&
											!members.some((member) => member.id === task.assigned_to) && (
												<option value={String(task.assigned_to)}>
													{task.assignee?.name ?? 'Persona asignada anteriormente'}
												</option>
											)}
										{members.map((member) => (
											<option key={member.id} value={String(member.id)}>
												{member.name}
											</option>
										))}
									</select>
								</div>
							</>
						)}
						{Object.entries(action === 'edit' ? edit.errors : mutation.errors).map(
							([field, message]) => (
								<p key={field} role="alert" className="text-sm text-destructive">
									{message}
								</p>
							)
						)}
						<DialogFooter>
							<Button
								type="button"
								variant="outline"
								disabled={processing}
								onClick={() => setAction(null)}
							>
								Cancelar
							</Button>
							<Button
								type="submit"
								variant={action === 'delete' ? 'destructive' : 'default'}
								disabled={processing}
							>
								{action === 'edit' ? 'Guardar cambios' : labels[action]}
							</Button>
						</DialogFooter>
					</form>
				)}
			</DialogContent>
		</Dialog>
	);
}
