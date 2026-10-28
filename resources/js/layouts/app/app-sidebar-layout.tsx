import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
	return (
		<AppShell variant="sidebar">
			<a
				href="#workspace-content"
				className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-primary focus:px-4 focus:py-3 focus:text-primary-foreground focus:shadow-lg"
			>
				Saltar al contenido
			</a>
			<AppSidebar />
			<AppContent variant="sidebar" className="overflow-x-hidden">
				<AppSidebarHeader breadcrumbs={breadcrumbs} />
				<div
					id="workspace-content"
					tabIndex={-1}
					className="flex min-w-0 flex-1 flex-col focus:outline-none"
				>
					{children}
				</div>
			</AppContent>
		</AppShell>
	);
}
