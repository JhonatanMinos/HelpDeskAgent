import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useId, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store as storeTask } from '@/routes/dev/tasks';

interface Member {
	id: number;
	name: string;
}

interface Props {
	currentTeamSlug: string;
	projectId: number;
	columnId: number;
	members?: Member[]; // opcional; si no lo tienes armado, se omite el select de asignado
}

export function NewTaskDialog({ currentTeamSlug, projectId, columnId, members = [] }: Props) {
	const [open, setOpen] = useState(false);
	const formId = useId();

	const { data, setData, post, processing, errors, reset } = useForm({
		boards_column_id: columnId,
		title: '',
		description: '',
		priority: 'media' as 'baja' | 'media' | 'alta',
		due_date: '',
		assigned_to: '' as string | number,
	});

	const submit = (e: React.FormEvent) => {
		e.preventDefault();

		post(storeTask({ current_team: currentTeamSlug, project: projectId }).url, {
			preserveScroll: true,
			preserveState: true,
			only: ['columns', 'flash'],
			onSuccess: () => {
				setOpen(false);
				reset();
			},
		});
	};

	return (
		<Dialog open={open} onOpenChange={setOpen}>
			<DialogTrigger asChild>
				<button
					type="button"
					className="mt-3 flex min-h-10 w-full items-center justify-center gap-1.5 rounded-xl border border-dashed px-3 py-2 text-xs font-medium text-muted-foreground transition hover:border-primary/40 hover:bg-primary/5 hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
				>
					<Plus size={14} /> Nueva tarea
				</button>
			</DialogTrigger>

			<DialogContent className="max-h-[85dvh] overflow-y-auto rounded-2xl sm:max-w-lg">
				<DialogHeader>
					<DialogTitle>Nueva tarea</DialogTitle>
				</DialogHeader>

				<form onSubmit={submit} className="space-y-4">
					<div>
						<label htmlFor={`${formId}-title`} className="text-sm font-medium">
							Título
						</label>
						<Input
							id={`${formId}-title`}
							value={data.title}
							onChange={(e) => setData('title', e.target.value)}
							className="mt-1"
							autoFocus
						/>
						{errors.title && <p className="mt-1 text-xs text-red-500">{errors.title}</p>}
					</div>

					<div>
						<label htmlFor={`${formId}-description`} className="text-sm font-medium">
							Descripción
						</label>
						<Textarea
							id={`${formId}-description`}
							value={data.description}
							onChange={(e) => setData('description', e.target.value)}
							rows={3}
							className="mt-1"
						/>
					</div>

					<div className="grid gap-3 sm:grid-cols-2">
						<div>
							<label htmlFor={`${formId}-priority`} className="text-sm font-medium">
								Prioridad
							</label>
							<Select
								value={data.priority}
								onValueChange={(v) => setData('priority', v as typeof data.priority)}
							>
								<SelectTrigger id={`${formId}-priority`} className="mt-1 w-full">
									<SelectValue />
								</SelectTrigger>
								<SelectContent>
									<SelectItem value="baja">Baja</SelectItem>
									<SelectItem value="media">Media</SelectItem>
									<SelectItem value="alta">Alta</SelectItem>
								</SelectContent>
							</Select>
						</div>

						<div>
							<label htmlFor={`${formId}-due_date`} className="text-sm font-medium">
								Fecha límite
							</label>
							<Input
								id={`${formId}-due_date`}
								type="date"
								value={data.due_date}
								onChange={(e) => setData('due_date', e.target.value)}
								className="mt-1"
							/>
							{errors.due_date && <p className="mt-1 text-xs text-red-500">{errors.due_date}</p>}
						</div>
					</div>

					{members.length > 0 && (
						<div>
							<label htmlFor={`${formId}-assigned_to`} className="text-sm font-medium">
								Asignar a
							</label>
							<Select
								value={String(data.assigned_to)}
								onValueChange={(v) => setData('assigned_to', v === 'unassigned' ? '' : Number(v))}
							>
								<SelectTrigger id={`${formId}-assigned_to`} className="mt-1 w-full">
									<SelectValue placeholder="Sin asignar" />
								</SelectTrigger>
								<SelectContent>
									<SelectItem value="unassigned">Sin asignar</SelectItem>
									{members.map((m) => (
										<SelectItem key={m.id} value={String(m.id)}>
											{m.name}
										</SelectItem>
									))}
								</SelectContent>
							</Select>
						</div>
					)}

					<DialogFooter>
						<Button type="submit" disabled={processing}>
							Crear tarea
						</Button>
					</DialogFooter>
				</form>
			</DialogContent>
		</Dialog>
	);
}
