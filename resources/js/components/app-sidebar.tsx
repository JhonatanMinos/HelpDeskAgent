import { Link, usePage } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
	Sidebar,
	SidebarContent,
	SidebarFooter,
	SidebarHeader,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
} from '@/components/ui/sidebar';
import { workspaceNavigation } from '@/lib/workspace-navigation';
import { dashboard } from '@/routes';

export function AppSidebar() {
	const page = usePage();
	const currentTeam = page.props.currentTeam;

	const dashboardUrl = currentTeam ? dashboard.url(currentTeam.slug) : '/';
	const mainNavItems = workspaceNavigation(currentTeam?.slug ?? null);

	return (
		<Sidebar collapsible="icon" variant="floating">
			<SidebarHeader className="gap-5 p-3">
				<SidebarMenu>
					<SidebarMenuItem>
						<SidebarMenuButton size="lg" asChild>
							<Link href={dashboardUrl} prefetch>
								<AppLogo />
							</Link>
						</SidebarMenuButton>
					</SidebarMenuItem>
				</SidebarMenu>
				<SidebarMenu>
					<SidebarMenuItem>
						<TeamSwitcher />
					</SidebarMenuItem>
				</SidebarMenu>
			</SidebarHeader>

			<SidebarContent>
				<NavMain items={mainNavItems} />
			</SidebarContent>

			<SidebarFooter className="border-t border-sidebar-border p-3">
				<NavUser />
			</SidebarFooter>
		</Sidebar>
	);
}
