import { usePage } from '@inertiajs/react';

type SharedProps = {
	auth: {
		user: {
			current_team: { id: number; slug: string };
		};
	};
};

export function useCurrentTeam() {
	const { auth } = usePage<SharedProps>().props;

	return auth.user.current_team;
}
