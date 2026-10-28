import { Head, Link } from '@inertiajs/react';
import { Eye, LogOut, Pencil, Plus, UsersRound } from 'lucide-react';
import { useState } from 'react';
import CreateTeamModal from '@/components/create-team-modal';
import Heading from '@/components/heading';
import LeaveTeamModal from '@/components/leave-team-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { edit, index } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
	teams: Team[];
};

export default function TeamsIndex({ teams }: Props) {
	const [leaveTeamDialogOpen, setLeaveTeamDialogOpen] = useState(false);
	const [teamLeaving, setTeamLeaving] = useState<Team | null>(null);

	const openLeaveTeamDialog = (team: Team) => {
		setTeamLeaving(team);
		setLeaveTeamDialogOpen(true);
	};

	return (
		<>
			<Head title="Teams" />

			<h1 className="sr-only">Teams</h1>

			<div className="flex flex-col space-y-6">
				<div className="flex flex-wrap items-start justify-between gap-4">
					<Heading
						variant="small"
						title="Teams"
						description="Manage your teams and team memberships"
					/>

					<CreateTeamModal>
						<Button data-test="teams-new-team-button">
							<Plus /> New team
						</Button>
					</CreateTeamModal>
				</div>

				{teams.length === 0 && (
					<div className="rounded-xl border border-dashed bg-muted/30 px-5 py-10 text-center">
						<UsersRound className="mx-auto mb-3 size-7 text-muted-foreground" />
						<p className="text-sm font-medium">No teams yet</p>
						<p className="mt-1 text-sm text-muted-foreground">
							Create a team to organize your workspace.
						</p>
					</div>
				)}
				<div className="space-y-3">
					{teams.map((team) => {
						const canLeaveTeam = !team.isPersonal && team.role !== 'owner';

						return (
							<div
								key={team.id}
								data-test="team-row"
								className="flex flex-wrap items-center justify-between gap-4 rounded-xl border bg-background/50 p-4 transition-colors hover:border-primary/30"
							>
								<div className="flex min-w-0 flex-1 items-center gap-3">
									<div className="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
										<UsersRound className="size-5" />
									</div>
									<div>
										<div className="flex items-center gap-2">
											<span className="font-medium">{team.name}</span>
											{team.isPersonal ? <Badge variant="secondary">Personal</Badge> : null}
										</div>
										<span className="text-sm text-muted-foreground">{team.roleLabel}</span>
									</div>
								</div>

								<TooltipProvider>
									<div className="flex items-center gap-2">
										{canLeaveTeam ? (
											<Tooltip>
												<TooltipTrigger asChild>
													<Button
														variant="ghost"
														size="sm"
														data-test="team-leave-button"
														aria-label={`Leave ${team.name}`}
														onClick={() => openLeaveTeamDialog(team)}
													>
														<LogOut className="h-4 w-4" />
													</Button>
												</TooltipTrigger>
												<TooltipContent>
													<p>Leave team</p>
												</TooltipContent>
											</Tooltip>
										) : null}

										{team.role === 'member' ? (
											<Tooltip>
												<TooltipTrigger asChild>
													<Button
														variant="ghost"
														size="sm"
														data-test="team-view-button"
														aria-label={`View ${team.name}`}
														asChild
													>
														<Link href={edit(team.slug)}>
															<Eye className="h-4 w-4" />
														</Link>
													</Button>
												</TooltipTrigger>
												<TooltipContent>
													<p>View team</p>
												</TooltipContent>
											</Tooltip>
										) : (
											<Tooltip>
												<TooltipTrigger asChild>
													<Button
														variant="ghost"
														size="sm"
														data-test="team-edit-button"
														aria-label={`Edit ${team.name}`}
														asChild
													>
														<Link href={edit(team.slug)}>
															<Pencil className="h-4 w-4" />
														</Link>
													</Button>
												</TooltipTrigger>
												<TooltipContent>
													<p>Edit team</p>
												</TooltipContent>
											</Tooltip>
										)}
									</div>
								</TooltipProvider>
							</div>
						);
					})}

					{teams.length === 0 ? (
						<p className="py-8 text-center text-muted-foreground">
							You don't belong to any teams yet.
						</p>
					) : null}
				</div>
			</div>

			<LeaveTeamModal
				team={teamLeaving}
				open={leaveTeamDialogOpen}
				onOpenChange={setLeaveTeamDialogOpen}
			/>
		</>
	);
}

TeamsIndex.layout = {
	breadcrumbs: [
		{
			title: 'Teams',
			href: index(),
		},
	],
};
