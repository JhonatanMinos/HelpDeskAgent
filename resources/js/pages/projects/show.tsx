import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { NewTaskDialog } from '@/components/dev/new-task-dialog';
import { Badge } from '@/components/ui/badge';
import { Card, CardAction, CardDescription, CardFooter, CardHeader } from '@/components/ui/card';
import { useCurrentTeam } from '@/hooks/use-current-team';
import { index as projectsIndex, show as showProject } from '@/routes/dev/projects';
import { move as moveTask } from '@/routes/dev/tasks';

interface Task {
	id: number;
	title: string;
	priority: 'baja' | 'media' | 'alta';
	assignee: { name: string } | null;
}

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
}

const PRIORITY_COLOR: Record<Task['priority'], string> = {
	baja: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
	media: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
	alta: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
};

export default function KanbanBoard() {
	const currentTeam = useCurrentTeam();
	const { project, columns } = usePage<PageProps>().props;

	const [board, setBoard] = useState<Column[]>(columns);
	const [dragged, setDragged] = useState<{
		taskId: number;
		fromColumn: number;
	} | null>(null);

	useEffect(() => {
		setBoard(columns);
	}, [columns]);

	const handleDrop = (toColumnId: number) => {
		if (!dragged) return;

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
		setDragged(null);

		const targetColumn = next.find((c) => c.id === toColumnId)!;

		router.patch(
			moveTask({ current_team: currentTeam.slug, task: dragged.taskId }).url,
			{
				boards_column_id: toColumnId,
				ordered_task_ids: targetColumn.tasks.map((t) => t.id),
			},
			{ preserveScroll: true, preserveState: true }
		);
	};

	return (
		<>
			<Head title={project.name} />

			<div className="p-4">
				<h1 className="mb-6 text-xl font-semibold tracking-tight">{project.name}</h1>

				<div className="flex gap-4 overflow-x-auto pb-4">
					{board.map((column) => (
						<div
							key={column.id}
							onDragOver={(e) => e.preventDefault()}
							onDrop={() => handleDrop(column.id)}
							className="w-72 shrink-0 rounded-lg border bg-muted/30 p-3"
						>
							<div className="mb-3 flex items-center justify-between">
								<h2 className="text-sm font-medium">{column.name}</h2>
								<span className="text-xs text-muted-foreground">{column.tasks.length}</span>
							</div>
							<div className="space-y-2">
								{column.tasks.map((task) => (
									<Card
										key={task.id}
										draggable
										onDragStart={() =>
											setDragged({
												taskId: task.id,
												fromColumn: column.id,
											})
										}
										className="cursor-grabbing bg-card p-3 shadow-lg active:cursor-grabbing"
									>
										<CardHeader>
											<CardDescription>#{task.id}</CardDescription>
											<CardAction>
												<Badge className={`${PRIORITY_COLOR[task.priority]}`}>
													{task.priority}
												</Badge>
											</CardAction>
										</CardHeader>
										<CardFooter className="flex-col items-start gap-1.5 text-sm">
											<div className="line-clamp-1 flex gap-2 font-medium">{task.title}</div>
										</CardFooter>
									</Card>
								))}
							</div>
							<NewTaskDialog
								currentTeamSlug={currentTeam.slug}
								projectId={project.id}
								columnId={column.id}
							/>
						</div>
					))}
				</div>
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
