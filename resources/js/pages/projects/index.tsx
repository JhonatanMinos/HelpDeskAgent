import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { KanbanSquare, Plus } from 'lucide-react';
import { useState } from 'react';
import { type EditableProject, ProjectActions } from '@/components/dev/project-actions';
import { Badge } from '@/components/ui/badge';
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
import { Textarea } from '@/components/ui/textarea';
import { useCurrentTeam } from '@/hooks/use-current-team';
import {
	index as projectsIndex,
	show as showProject,
	store as storeProject,
} from '@/routes/dev/projects';

interface Project extends EditableProject {
	due_date: string | null;
	tasks_count: number;
	creator: { name: string };
}

interface PageProps extends InertiaPageProps {
	projects: Project[];
}

const STATUS_LABEL: Record<Project['status'], string> = {
	propuesto: 'Propuesto',
	activo: 'Activo',
	pausado: 'Pausado',
	completado: 'Completado',
};

const STATUS_CLASS: Record<Project['status'], string> = {
	propuesto: 'bg-slate-500/15 text-slate-500',
	activo: 'bg-emerald-500/15 text-emerald-500',
	pausado: 'bg-amber-500/15 text-amber-500',
	completado: 'bg-blue-500/15 text-blue-500',
};

function NewProjectDialog() {
	const currentTeam = useCurrentTeam();
	const [open, setOpen] = useState(false);

	const { data, setData, post, processing, errors, reset } = useForm({
		name: '',
		requirements: '',
		due_date: '',
	});

	const submit = (e: React.FormEvent) => {
		e.preventDefault();
		post(storeProject({ current_team: currentTeam.slug }).url, {
			onSuccess: () => {
				setOpen(false);
				reset();
			},
		});
	};

	return (
		<Dialog open={open} onOpenChange={setOpen}>
			<DialogTrigger asChild>
				<Button>
					<Plus size={16} />
					Nuevo proyecto
				</Button>
			</DialogTrigger>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Nuevo proyecto Dev</DialogTitle>
				</DialogHeader>

				<form onSubmit={submit} className="space-y-4">
					<div>
						<label htmlFor="project-create-name" className="text-sm font-medium">
							Nombre
						</label>
						<Input
							id="project-create-name"
							value={data.name}
							onChange={(e) => setData('name', e.target.value)}
							className="mt-1"
						/>
						{errors.name && <p className="mt-1 text-xs text-destructive">{errors.name}</p>}
					</div>

					<div>
						<label htmlFor="project-create-requirements" className="text-sm font-medium">
							Requerimientos iniciales
						</label>
						<Textarea
							id="project-create-requirements"
							value={data.requirements}
							onChange={(e) => setData('requirements', e.target.value)}
							rows={4}
							className="mt-1"
						/>
					</div>

					<div>
						<label htmlFor="project-create-due_date" className="text-sm font-medium">
							Fecha estimada de entrega
						</label>
						<Input
							id="project-create-due_date"
							type="date"
							value={data.due_date}
							onChange={(e) => setData('due_date', e.target.value)}
							className="mt-1"
						/>
						{errors.due_date && <p className="mt-1 text-xs text-destructive">{errors.due_date}</p>}
					</div>

					<DialogFooter>
						<Button type="submit" disabled={processing}>
							Crear proyecto
						</Button>
					</DialogFooter>
				</form>
			</DialogContent>
		</Dialog>
	);
}

export default function DevProjectsIndex() {
	const currentTeam = useCurrentTeam();
	const { projects } = usePage<PageProps>().props;

	return (
		<>
			<Head title="Proyectos Dev" />

			<div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
				<div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/10 via-card to-card p-5 md:p-6">
					<div>
						<p className="mb-2 text-xs font-semibold uppercase tracking-widest text-primary">
							Espacio de trabajo
						</p>
						<h1 className="text-2xl font-semibold tracking-tight md:text-3xl">Proyectos Dev</h1>
						<p className="text-sm text-muted-foreground">
							Gestión de proyectos y tableros Kanban del equipo.
						</p>
					</div>
					<NewProjectDialog />
				</div>

				{projects.length === 0 ? (
					<div className="rounded-2xl border border-dashed bg-card px-6 py-16 text-center">
						<KanbanSquare className="mx-auto mb-3 text-muted-foreground" size={32} />
						<p className="text-sm text-muted-foreground">
							No hay proyectos todavía. Crea el primero para arrancar un tablero.
						</p>
					</div>
				) : (
					<div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
						{projects.map((project) => {
							const dueDate = project.estimated_date_delivery ?? project.due_date;
							return (
								<article
									key={project.id}
									className="min-w-0 space-y-4 rounded-2xl border bg-card p-5 shadow-sm transition hover:border-primary/35 hover:shadow-md"
								>
									<Link
										href={
											showProject({
												current_team: currentTeam.slug,
												project: project.id,
											}).url
										}
										className="block rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
									>
										<div className="mb-4 flex flex-wrap items-start justify-between gap-3">
											<h2 className="break-words text-base font-semibold">{project.name}</h2>
											<Badge variant="outline" className={STATUS_CLASS[project.status]}>
												{STATUS_LABEL[project.status]}
											</Badge>
										</div>
										<p className="text-xs text-muted-foreground">
											{project.tasks_count} {project.tasks_count === 1 ? 'tarea' : 'tareas'} ·
											creado por {project.creator.name}
										</p>
										{dueDate && (
											<p className="mt-1 text-xs text-muted-foreground">
												Entrega: {new Date(dueDate).toLocaleDateString('es-MX')}
											</p>
										)}
									</Link>
									<ProjectActions project={project} teamSlug={currentTeam.slug} />
								</article>
							);
						})}
					</div>
				)}
			</div>
		</>
	);
}

DevProjectsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
	breadcrumbs: [
		{
			title: 'Proyectos Dev',
			href: props.currentTeam ? projectsIndex(props.currentTeam.slug) : '/',
		},
	],
});
