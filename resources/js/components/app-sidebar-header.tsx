import { Breadcrumbs } from '@/components/breadcrumbs';
import CommandBar from '@/components/CommandBar/CommandBar';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
	return (
		<header className="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-border/70 bg-background/90 px-4 backdrop-blur-md md:px-6">
			<div className="flex min-w-0 items-center gap-3 overflow-hidden">
				<SidebarTrigger className="-ml-1" />
				<Breadcrumbs breadcrumbs={breadcrumbs} />
			</div>
			<CommandBar />
		</header>
	);
}
