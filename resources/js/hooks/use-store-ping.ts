import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ping } from '@/routes/branches';

export function useStatePing(currentTeamSlug: string) {
	const [pingingIds, setPingingIds] = useState<Set<number>>(new Set());

	const pingStore = (storeId: number) => {
		setPingingIds((prev) => new Set(prev).add(storeId));
		router.post(
			ping({ current_team: currentTeamSlug, store: storeId }).url,
			{},
			{
				preserveScroll: true,
				preserveState: true,
				only: ['stores', 'flash'],
				onFinish: () => {
					setPingingIds((prev) => {
						const next = new Set(prev);
						next.delete(storeId);
						return next;
					});
				},
			}
		);
	};

	return { pingStore, isPinging: (id: number) => pingingIds.has(id) };
}
