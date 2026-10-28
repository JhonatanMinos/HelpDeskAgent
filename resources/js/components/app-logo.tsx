import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
	return (
		<>
			<div className="flex aspect-square size-9 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-sm">
				<AppLogoIcon className="size-5" />
			</div>
			<div className="ml-1 grid flex-1 text-left text-sm">
				<span className="truncate font-semibold leading-tight tracking-tight">HelpDeskAgent</span>
				<span className="mt-0.5 truncate text-[10px] uppercase tracking-widest text-muted-foreground">
					Operations workspace
				</span>
			</div>
		</>
	);
}
