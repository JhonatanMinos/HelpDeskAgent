import { router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
	Command,
	CommandEmpty,
	CommandInput,
	CommandItem,
	CommandList,
} from '@/components/ui/command';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { workspaceNavigation } from '@/lib/workspace-navigation';

export default function CommandBar() {
	const [open, setOpen] = useState(false);
	const { currentTeam } = usePage().props;
	const destinations = currentTeam ? workspaceNavigation(currentTeam.slug) : [];

	useEffect(() => {
		const down = (event: KeyboardEvent) => {
			if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
				event.preventDefault();
				setOpen((previous) => !previous);
			}
		};
		document.addEventListener('keydown', down);
		return () => document.removeEventListener('keydown', down);
	}, []);

	return (
		<>
			<Button
				variant="outline"
				size="sm"
				onClick={() => setOpen(true)}
				aria-label="Buscar una sección"
				aria-haspopup="dialog"
				className="gap-2 rounded-lg bg-background/70 text-muted-foreground shadow-none"
			>
				<Search className="size-4" />
				<span className="hidden sm:inline">Ir a una sección</span>
				<kbd className="hidden rounded border px-1.5 text-[10px] md:inline">Ctrl / ⌘ K</kbd>
			</Button>
			<Dialog open={open} onOpenChange={setOpen}>
				<DialogContent className="overflow-hidden p-0 sm:max-w-lg" showCloseButton={false}>
					<DialogTitle className="sr-only">Navegación rápida</DialogTitle>
					<DialogDescription className="sr-only">
						Busca una sección del equipo actual. Usa las flechas para elegir y Enter para abrir.
					</DialogDescription>
					<Command>
						<CommandInput placeholder="¿A dónde quieres ir?" aria-label="Buscar una sección" />
						<CommandList className="p-2">
							<CommandEmpty>
								{currentTeam
									? 'No se encontraron secciones.'
									: 'Selecciona un equipo para navegar.'}
							</CommandEmpty>
							{destinations.map((item) => (
								<CommandItem
									key={item.title}
									onSelect={() => {
										setOpen(false);
										router.visit(item.href);
									}}
									className="gap-3 rounded-lg py-3"
								>
									{item.icon && <item.icon className="size-4" />}
									{item.title}
								</CommandItem>
							))}
						</CommandList>
					</Command>
				</DialogContent>
			</Dialog>
		</>
	);
}
