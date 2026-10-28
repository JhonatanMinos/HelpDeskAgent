import { Link } from '@inertiajs/react';
import { Fingerprint, Paintbrush, UserRound, UsersRound } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as teams } from '@/routes/teams';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
	{
		title: 'Profile',
		href: edit(),
		icon: UserRound,
	},
	{
		title: 'Security',
		href: editSecurity(),
		icon: Fingerprint,
	},
	{
		title: 'Teams',
		href: teams(),
		icon: UsersRound,
	},
	{
		title: 'Appearance',
		href: editAppearance(),
		icon: Paintbrush,
	},
];

export default function SettingsLayout({ children }: PropsWithChildren) {
	const { isCurrentOrParentUrl } = useCurrentUrl();

	return (
		<div className="mx-auto w-full max-w-7xl px-4 py-6 md:px-6">
			<p className="mb-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
				Personal workspace
			</p>
			<Heading title="Settings" description="Manage your profile and account settings" />

			<div className="grid items-start gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8">
				<aside className="min-w-0 rounded-2xl border bg-card p-2 lg:sticky lg:top-24">
					<nav className="flex gap-1 overflow-x-auto lg:flex-col" aria-label="Settings">
						{sidebarNavItems.map((item) => (
							<Button
								key={toUrl(item.href)}
								size="sm"
								variant="ghost"
								asChild
								className={cn('h-10 shrink-0 justify-start rounded-xl px-3 lg:w-full', {
									'bg-primary/10 text-primary hover:bg-primary/15 hover:text-primary':
										isCurrentOrParentUrl(item.href),
								})}
							>
								<Link
									href={item.href}
									aria-current={isCurrentOrParentUrl(item.href) ? 'page' : undefined}
								>
									{item.icon && <item.icon className="h-4 w-4" />}
									{item.title}
								</Link>
							</Button>
						))}
					</nav>
				</aside>

				<div className="min-w-0 max-w-3xl">
					<section className="min-w-0 space-y-6 [&>div]:rounded-2xl [&>div]:border [&>div]:bg-card [&>div]:p-5 sm:[&>div]:p-6">
						{children}
					</section>
				</div>
			</div>
		</div>
	);
}
