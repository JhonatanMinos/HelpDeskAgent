import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type PaginationLinks = {
	prev_page_url: string | null;
	next_page_url: string | null;
	current_page?: number;
	last_page?: number;
	from?: number | null;
	to?: number | null;
	total?: number;
};

export function PaginationGeneric({ pagination }: { pagination: PaginationLinks }) {
	const controls = [
		{
			href: pagination.prev_page_url,
			label: 'Anterior',
			accessibleLabel: 'Página anterior',
			icon: ChevronLeft,
		},
		{
			href: pagination.next_page_url,
			label: 'Siguiente',
			accessibleLabel: 'Página siguiente',
			icon: ChevronRight,
		},
	];
	return (
		<div className="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3">
			<div className="text-xs text-muted-foreground" role="status">
				{pagination.total !== undefined ? (
					<span>
						{pagination.from != null && pagination.to != null
							? `${pagination.from}–${pagination.to} de ${pagination.total}`
							: `${pagination.total} resultados`}
					</span>
				) : (
					<span>Navegación de resultados</span>
				)}
				{pagination.current_page !== undefined && pagination.last_page !== undefined && (
					<span className="ml-3">
						Página {pagination.current_page} de {pagination.last_page}
					</span>
				)}
			</div>
			<nav aria-label="Paginación de resultados" className="flex items-center gap-2">
				{controls.map(({ href, label, accessibleLabel, icon: Icon }) => {
					const className = cn(
						buttonVariants({ variant: 'outline', size: 'sm' }),
						'gap-1.5 rounded-lg'
					);
					return href ? (
						<Link
							key={label}
							href={href}
							preserveScroll
							aria-label={accessibleLabel}
							className={className}
						>
							<Icon className="size-3.5" />
							{label}
						</Link>
					) : (
						<button
							key={label}
							type="button"
							disabled
							aria-disabled="true"
							aria-label={accessibleLabel}
							className={cn(className, 'cursor-not-allowed opacity-40')}
						>
							<Icon className="size-3.5" />
							{label}
						</button>
					);
				})}
			</nav>
		</div>
	);
}
