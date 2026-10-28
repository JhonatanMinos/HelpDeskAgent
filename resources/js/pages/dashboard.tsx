import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { DashboardSummary } from '@/components/dashboard-summary';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { useCurrentTeam } from '@/hooks/use-current-team';
import { dashboard } from '@/routes';
import type { DashboardInvitation } from '@/types';
import type { DashboardOverview } from '@/types/dashboard';

type Props = {
	overview: DashboardOverview;
	pendingInvitations?: DashboardInvitation[];
};

export default function Dashboard({ overview, pendingInvitations = [] }: Props) {
	const team = useCurrentTeam();
	const [showInvitations, setShowInvitations] = useState(pendingInvitations.length > 0);

	return (
		<>
			<Head title="Dashboard" />
			<PendingInvitationsModal
				invitations={pendingInvitations}
				open={pendingInvitations.length > 0 && showInvitations}
				onOpenChange={setShowInvitations}
			/>
			<DashboardSummary overview={overview} teamSlug={team.slug} />
		</>
	);
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
	breadcrumbs: [
		{
			title: 'Dashboard',
			href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
		},
	],
});
