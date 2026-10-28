import { Link } from '@inertiajs/react';
import {
	SidebarGroup,
	SidebarGroupLabel,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { toUrl } from '@/lib/utils';
import { isWorkspaceSectionActive } from '@/lib/workspace-navigation';
import type { NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
	const { currentUrl } = useCurrentUrl();

	return (
		<SidebarGroup className="px-2 py-0">
			<SidebarGroupLabel className="mb-2 text-[10px] uppercase tracking-[0.18em]">
				Espacio de trabajo
			</SidebarGroupLabel>
			<SidebarMenu className="gap-1.5">
				{items.map((item) => (
					<SidebarMenuItem key={item.title}>
						<SidebarMenuButton
							asChild
							isActive={isWorkspaceSectionActive(currentUrl, toUrl(item.href))}
							className="h-10 rounded-xl text-sidebar-foreground/75 transition-colors data-[active=true]:bg-sidebar-accent data-[active=true]:font-semibold data-[active=true]:text-sidebar-primary"
							tooltip={{ children: item.title }}
						>
							<Link
								href={item.href}
								prefetch
								aria-current={
									isWorkspaceSectionActive(currentUrl, toUrl(item.href)) ? 'page' : undefined
								}
							>
								{item.icon && <item.icon />}
								<span>{item.title}</span>
							</Link>
						</SidebarMenuButton>
					</SidebarMenuItem>
				))}
			</SidebarMenu>
		</SidebarGroup>
	);
}
