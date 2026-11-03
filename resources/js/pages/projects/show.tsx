import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { NewTaskDialog } from '@/components/dev/new-task-dialog';
import { type EditableTask, TaskActions, type TaskMember } from '@/components/dev/task-actions';
import { Badge } from '@/components/ui/badge';
import { Card, CardAction, CardDescription, CardFooter, CardHeader } from '@/components/ui/card';
import { useCurrentTeam } from '@/hooks/use-current-team';
import { index as projectsIndex, show as showProject } from '@/routes/dev/projects';
import { move as moveTask } from '@/routes/dev/tasks';

type Task = EditableTask;

interface Column {
	id: number;
	name: string;
	order: number;
	tasks: Task[];
}

interface Project {
	id: number;
	name: string;
	status: string;
}

interface PageProps extends InertiaPageProps {
	project: Project;
	columns: Column[];
	archivedTasks?: Task[];
	members?: TaskMember[];
	taskArchivingAvailable?: boolean;
}

const PRIORITY_COLOR: Record<Task['priority'], string> = {
	baja: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
	media: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
	alta: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
};

export default function KanbanBoard() {
	const currentTeam = useCurrentTeam();
	const {
		project,
		columns,
		archivedTasks = [],
		members = [],
		taskArchivingAvailable = true,
	} = usePage<PageProps>().props;

	const [board, setBoard] = useState<Column[]>(columns);
	const [moving, setMoving] = useState(false);
	const [dragged, setDragged] = useState<{
		taskId: number;
		fromColumn: number;
	} | null>(null);

	useEffect(() => {
		setBoard(columns);
	}, [columns]);

	const handleDrop = (toColumnId: number) => {
		if (!dragged || moving) return;
		if (dragged.fromColumn === toColumnId) {
			setDragged(null);
			return;
		}
		const previousBoard = board;

		const next = board.map((col) => {
			if (col.id === dragged.fromColumn) {
				return {
					...col,
					tasks: col.tasks.filter((t) => t.id !== dragged.taskId),
				};
			}
			if (col.id === toColumnId) {
				const moved = board
					.find((c) => c.id === dragged.fromColumn)!
					.tasks.find((t) => t.id === dragged.taskId)!;
				return { ...col, tasks: [...col.tasks, moved] };
			}
			return col;
		});

		setBoard(next);
		setMoving(true);
		setDragged(null);

		const targetColumn = next.find((c) => c.id === toColumnId)!;

		router.patch(
			moveTask({ current_team: currentTeam.slug, task: dragged.taskId }).url,
			{
				boards_column_id: toColumnId,
				ordered_task_ids: targetColumn.tasks.map((t) => t.id),
			},
			{
				preserveScroll: true,
				preserveState: true,
				onError: () => setBoard(previousBoard),
				onCancel: () => setBoard(previousBoard),
				onFinish: () => setMoving(false),
			}
		);
	};

	return (
		<>
			<Head title={project.name} />

			<div className="flex min-w-0 flex-col gap-6 p-4 md:p-6">
				<header className="rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/10 via-card to-card p-5 md:p-6">
					<p className="mb-2 text-xs font-semibold uppercase tracking-widest text-primary">
						Tablero del equipo
					</p>
					<h1 className="break-words text-2xl font-semibold tracking-tight md:text-3xl">
						{project.name}
					</h1>
					<p className="mt-2 text-sm text-muted-foreground">
						Organiza las tareas por etapa y arrástralas para actualizar su avance.
					</p>
				</header>

				<div className="flex gap-4 overflow-x-auto pb-4">
					{board.map((column) => (
						<fieldset
							key={column.id}
							aria-label={`Etapa ${column.name}`}
							onDragOver={(e) => e.preventDefault()}
							onDrop={() => handleDrop(column.id)}
							className="w-72 shrink-0 rounded-2xl border bg-muted/40 p-3 md:w-80"
						>
							<div className="mb-3 flex items-center justify-between">
								<h2 className="text-sm font-medium">{column.name}</h2>
								<span className="grid min-w-6 place-items-center rounded-full bg-background px-1.5 py-0.5 text-xs font-medium text-muted-foreground">
									{column.tasks.length}
								</span>
							</div>
							<div className="space-y-2">
								{column.tasks.length === 0 && (
									<p className="rounded-xl border border-dashed px-3 py-8 text-center text-xs text-muted-foreground">
										Sin tareas en esta columna
									</p>
								)}
								{column.tasks.map((task) => (
									<Card
										key={task.id}
										draggable={!moving}
										onDragStart={() =>
											setDragged({
												taskId: task.id,
												fromColumn: column.id,
											})
										}
										className="cursor-grab gap-3 rounded-xl bg-card p-3 shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
									>
										<CardHeader>
											<CardDescription>#{task.id}</CardDescription>
											<CardAction className="flex items-center gap-1">
												<Badge className={`${PRIORITY_COLOR[task.priority]}`}>
													{task.priority}
												</Badge>
												<TaskActions
													task={task}
													teamSlug={currentTeam.slug}
													members={members}
													archivingAvailable={taskArchivingAvailable}
												/>
											</CardAction>
										</CardHeader>
										<CardFooter className="flex-col items-start gap-1.5 text-sm">
											<div className="line-clamp-1 flex gap-2 font-medium">{task.title}</div>
											{task.assignee && (
												<span className="text-xs text-muted-foreground">{task.assignee.name}</span>
											)}
										</CardFooter>
									</Card>
								))}
							</div>
							<NewTaskDialog
								currentTeamSlug={currentTeam.slug}
								projectId={project.id}
								columnId={column.id}
								members={members}
							/>
						</fieldset>
					))}
				</div>
				<details className="rounded-2xl border bg-card p-4">
					<summary className="cursor-pointer text-sm font-medium">
						Tareas archivadas ({archivedTasks.length})
					</summary>
					{!taskArchivingAvailable && (
						<p role="status" className="mt-3 text-sm text-muted-foreground">
							El archivado estará disponible al aplicar la migración de tareas. Las tareas siguen
							visibles en el tablero.
						</p>
					)}
					{archivedTasks.length === 0 ? (
						<p className="mt-3 text-sm text-muted-foreground">No hay tareas archivadas.</p>
					) : (
						<ul className="mt-4 space-y-2">
							{archivedTasks.map((task) => (
								<li
									key={task.id}
									className="flex items-center justify-between gap-3 rounded-xl border p-3"
								>
									<div className="min-w-0">
										<p className="break-words text-sm font-medium">{task.title}</p>
										<p className="text-xs text-muted-foreground">{task.column?.name}</p>
									</div>
									<TaskActions
										task={task}
										teamSlug={currentTeam.slug}
										members={members}
										archivingAvailable={taskArchivingAvailable}
									/>
								</li>
							))}
						</ul>
					)}
				</details>
			</div>
		</>
	);
}

KanbanBoard.layout = (props: { currentTeam?: { slug: string } | null; project: Project }) => ({
	breadcrumbs: [
		{
			title: 'Proyectos Dev',
			href: props.currentTeam ? projectsIndex(props.currentTeam.slug) : '/',
		},
		{
			title: props.project.name,
			href: props.currentTeam
				? showProject({
						current_team: props.currentTeam.slug,
						project: props.project.id,
					}).url
				: '/',
		},
	],
});
