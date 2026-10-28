import { Building2, FolderKanban, LayoutDashboard, Tickets } from 'lucide-react';
import { dashboard } from '@/routes';
import { index as branches } from '@/routes/branches';
import { index as projects } from '@/routes/dev/projects';
import { index as tickets } from '@/routes/tickets';
import type { NavItem } from '@/types';

export function workspaceNavigation(teamSlug: string | null): NavItem[] {
	return [
		{ title: 'Dashboard', href: teamSlug ? dashboard.url(teamSlug) : '/', icon: LayoutDashboard },
		{ title: 'Sucursales', href: teamSlug ? branches.url(teamSlug) : '/', icon: Building2 },
		{ title: 'Tickets', href: teamSlug ? tickets.url(teamSlug) : '/', icon: Tickets },
		{ title: 'Proyectos', href: teamSlug ? projects.url(teamSlug) : '/', icon: FolderKanban },
	];
}

export function isWorkspaceSectionActive(currentUrl: string, href: string): boolean {
	const path = currentUrl.split(/[?#]/)[0];
	return path === href || (href !== '/' && path.startsWith(`${href}/`));
}
