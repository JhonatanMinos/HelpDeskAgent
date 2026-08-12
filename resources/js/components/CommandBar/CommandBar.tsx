import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
	Command,
	CommandEmpty,
	CommandInput,
	CommandItem,
	CommandList,
} from '@/components/ui/command';

export default function CommandBar() {
	const [open, setOpen] = useState(false);

	useEffect(() => {
		const down = (e: KeyboardEvent) => {
			if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
				e.preventDefault();
				setOpen((o) => !o);
			}
		};
		document.addEventListener('keydown', down);
		return () => document.removeEventListener('keydown', down);
	}, []);

	if (!open) return null;

	return (
		<div className="fixed inset-0 bg-black/40">
			<div className="mx-auto mt-32 w-[600px] rounded-lg bg-white shadow-xl">
				<Command>
					<CommandInput placeholder="Buscar..." />

					<CommandList>
						<CommandEmpty>Sin resultados</CommandEmpty>

						<CommandItem onSelect={() => router.visit('/dashboard')}>Dashboard</CommandItem>

						<CommandItem onSelect={() => router.visit('/users')}>Usuarios</CommandItem>

						<CommandItem onSelect={() => router.visit('/products')}>Productos</CommandItem>
					</CommandList>
				</Command>
			</div>
		</div>
	);
}
