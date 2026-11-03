import { useForm } from '@inertiajs/react';
import { Pencil, RefreshCw, Trash2 } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { destroy, update, updateStatus } from '@/routes/dev/projects';

export interface EditableProject {
	id: number;
	name: string;
	status: 'propuesto' | 'activo' | 'pausado' | 'completado';
	Initial_requirements: string | null;
	estimated_date_delivery: string | null;
}

export function ProjectActions({
	project,
	teamSlug,
}: {
	project: EditableProject;
	teamSlug: string;
}) {
	const id = useId();
	const [action, setAction] = useState<'edit' | 'status' | 'delete' | null>(null);
	const edit = useForm({ name: '', requirements: '', due_date: '' });
	const status = useForm({ status: project.status });
	const deletion = useForm<{ project?: string }>({});
	const processing = edit.processing || status.processing || deletion.processing;
	const target = { current_team: teamSlug, project: project.id };
	const onSuccess = () => setAction(null);

	const openEdit = () => {
		edit.setData({
			name: project.name,
			requirements: project.Initial_requirements ?? '',
			due_date: project.estimated_date_delivery?.slice(0, 10) ?? '',
		});
		edit.clearErrors();
		setAction('edit');
	};

	return (
		<Dialog open={action !== null} onOpenChange={(open) => !open && !processing && setAction(null)}>
			<div className="flex flex-wrap gap-2 border-t pt-4">
				<Button
					type="button"
					variant="outline"
					size="sm"
					disabled={processing}
					onClick={openEdit}
					aria-label={`Editar ${project.name}`}
				>
					<Pencil aria-hidden="true" /> Editar
				</Button>
				<Button
					type="button"
					variant="outline"
					size="sm"
					disabled={processing}
					aria-label={`Cambiar estado de ${project.name}`}
					onClick={() => {
						status.setData('status', project.status);
						status.clearErrors();
						setAction('status');
					}}
				>
					<RefreshCw aria-hidden="true" /> Estado
				</Button>
				<Button
					type="button"
					variant="outline"
					size="sm"
					disabled={processing}
					className="text-destructive hover:text-destructive"
					aria-label={`Eliminar ${project.name}`}
					onClick={() => {
						deletion.clearErrors();
						setAction('delete');
					}}
				>
					<Trash2 aria-hidden="true" /> Eliminar
				</Button>
			</div>
			<DialogContent showCloseButton={!processing}>
				<DialogHeader>
					<DialogTitle>
						{action === 'edit'
							? 'Editar proyecto'
							: action === 'status'
								? 'Cambiar estado'
								: 'Eliminar proyecto'}
					</DialogTitle>
					<DialogDescription>
						{action === 'delete'
							? `Se eliminarán “${project.name}”, sus columnas y todas sus tareas. Los tickets vinculados se conservarán sin proyecto. Esta acción no se puede deshacer.`
							: project.name}
					</DialogDescription>
				</DialogHeader>
				{action === 'edit' && (
					<form
						className="space-y-4"
						onSubmit={(event) => {
							event.preventDefault();
							if (!processing) edit.patch(update(target).url, { preserveScroll: true, onSuccess });
						}}
					>
						<div className="space-y-1">
							<label htmlFor={`${id}-name`} className="text-sm font-medium">
								Nombre
							</label>
							<Input
								id={`${id}-name`}
								required
								maxLength={255}
								value={edit.data.name}
								disabled={processing}
								onChange={(event) => edit.setData('name', event.target.value)}
							/>
							{edit.errors.name && (
								<p role="alert" className="text-sm text-destructive">
									{edit.errors.name}
								</p>
							)}
						</div>
						<div className="space-y-1">
							<label htmlFor={`${id}-requirements`} className="text-sm font-medium">
								Requerimientos
							</label>
							<Textarea
								id={`${id}-requirements`}
								rows={4}
								value={edit.data.requirements}
								disabled={processing}
								onChange={(event) => edit.setData('requirements', event.target.value)}
							/>
							{edit.errors.requirements && (
								<p role="alert" className="text-sm text-destructive">
									{edit.errors.requirements}
								</p>
							)}
						</div>
						<div className="space-y-1">
							<label htmlFor={`${id}-due-date`} className="text-sm font-medium">
								Fecha estimada de entrega
							</label>
							<Input
								id={`${id}-due-date`}
								type="date"
								value={edit.data.due_date}
								disabled={processing}
								onChange={(event) => edit.setData('due_date', event.target.value)}
							/>
							{edit.errors.due_date && (
								<p role="alert" className="text-sm text-destructive">
									{edit.errors.due_date}
								</p>
							)}
						</div>
						<DialogFooter>
							<Button
								type="button"
								variant="outline"
								disabled={processing}
								onClick={() => setAction(null)}
							>
								Cancelar
							</Button>
							<Button type="submit" disabled={processing}>
								Guardar cambios
							</Button>
						</DialogFooter>
					</form>
				)}
				{action === 'status' && (
					<form
						className="space-y-4"
						onSubmit={(event) => {
							event.preventDefault();
							if (!processing)
								status.patch(updateStatus(target).url, { preserveScroll: true, onSuccess });
						}}
					>
						<label htmlFor={`${id}-status`} className="text-sm font-medium">
							Estado del proyecto
						</label>
						<select
							id={`${id}-status`}
							className="h-10 w-full rounded-md border bg-background px-3 text-sm"
							value={status.data.status}
							disabled={processing}
							onChange={(event) =>
								status.setData('status', event.target.value as EditableProject['status'])
							}
						>
							<option value="propuesto">Propuesto</option>
							<option value="activo">Activo</option>
							<option value="pausado">Pausado</option>
							<option value="completado">Completado</option>
						</select>
						{status.errors.status && (
							<p role="alert" className="text-sm text-destructive">
								{status.errors.status}
							</p>
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
							<Button type="submit" disabled={processing}>
								Actualizar estado
							</Button>
						</DialogFooter>
					</form>
				)}
				{action === 'delete' && (
					<form
						className="space-y-4"
						onSubmit={(event) => {
							event.preventDefault();
							if (!processing)
								deletion.delete(destroy(target).url, { preserveScroll: true, onSuccess });
						}}
					>
						{deletion.errors.project && (
							<p role="alert" className="text-sm text-destructive">
								{deletion.errors.project}
							</p>
						)}
						<DialogFooter>
							<Button
								type="button"
								variant="outline"
								autoFocus
								disabled={processing}
								onClick={() => setAction(null)}
							>
								Cancelar
							</Button>
							<Button type="submit" variant="destructive" disabled={processing}>
								Eliminar proyecto
							</Button>
						</DialogFooter>
					</form>
				)}
			</DialogContent>
		</Dialog>
	);
}
