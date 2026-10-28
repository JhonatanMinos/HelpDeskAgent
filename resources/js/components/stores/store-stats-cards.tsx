import { Building2, MapPin, Wifi } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type StoreDashboardStats = {
	total: number;
	onlineCount: number;
	companys: Array<{ company: number; total: number }>;
};

export function StoreStatsCards({ total, onlineCount, companys }: StoreDashboardStats) {
	return (
		<div className="grid gap-4 sm:grid-cols-3">
			<Card className="border-primary/15 bg-primary/5 shadow-none">
				<CardContent className="flex items-start justify-between gap-4">
					<div>
						<p className="text-sm text-muted-foreground">Sucursales registradas</p>
						<p className="mt-3 text-3xl font-semibold tracking-tight tabular-nums">{total}</p>
						<p className="mt-1 text-xs text-muted-foreground">En el equipo actual</p>
					</div>
					<Building2 className="size-5 text-primary" />
				</CardContent>
			</Card>
			<Card className="shadow-none">
				<CardContent className="flex items-start justify-between gap-4">
					<div>
						<p className="text-sm text-muted-foreground">En línea</p>
						<p className="mt-3 text-3xl font-semibold tracking-tight tabular-nums text-emerald-600 dark:text-emerald-400">
							{onlineCount}
							<span className="ml-2 text-sm font-normal text-muted-foreground">/ {total}</span>
						</p>
						<p className="mt-1 text-xs text-muted-foreground">Según la última comprobación</p>
					</div>
					<Wifi className="size-5 text-emerald-600 dark:text-emerald-400" />
				</CardContent>
			</Card>
			<Card className="shadow-none">
				<CardContent className="flex items-start justify-between gap-4">
					<div className="min-w-0">
						<p className="text-sm text-muted-foreground">Compañías</p>
						<div className="mt-3 flex flex-wrap gap-2">
							{companys.map((item) => (
								<span
									key={item.company}
									className="rounded-lg bg-muted px-2.5 py-1.5 text-sm font-medium"
								>
									{item.company}
									<span className="ml-2 text-muted-foreground">{item.total}</span>
								</span>
							))}
						</div>
						{companys.length === 0 && (
							<p className="mt-3 text-sm text-muted-foreground">Sin compañías registradas</p>
						)}
					</div>
					<MapPin className="size-5 shrink-0 text-muted-foreground" />
				</CardContent>
			</Card>
		</div>
	);
}
