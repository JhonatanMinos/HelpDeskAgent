import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Bug,
    FolderGit2,
    FolderKanban,
    GitBranch,
    LayoutGrid,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import { dashboard } from '@/routes';
import { index as branches } from '@/routes/branches';
import { index as projects } from '@/routes/dev/projects';
import { index as tickets } from '@/routes/tickets';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const currentTeam = page.props.currentTeam;

    // Si no hay team activo, no tiene sentido armar rutas que requieren slug.
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : '/';
    const branchesUrl = currentTeam ? branches(currentTeam.slug) : '/';
    const projectsUrl = currentTeam ? projects(currentTeam.slug) : '/';
    const ticketsUrl = currentTeam ? tickets(currentTeam.slug) : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Branches',
            href: branchesUrl.url,
            icon: GitBranch,
        },
        {
            title: 'Projects',
            href: projectsUrl.url,
            icon: FolderKanban,
        },
        {
            title: 'Tickets',
            href: ticketsUrl.url,
            icon: Bug,
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="floating">
            <SidebarHeader>
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

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
