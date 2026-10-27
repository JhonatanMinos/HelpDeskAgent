import { Head, Link } from '@inertiajs/react';
import {
	ArrowLeft,
	CreditCard,
	Fingerprint,
	Loader2,
	MapPin,
	Printer,
	Radar,
	Router,
	Server,
	Wifi,
	WifiOff,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { useCurrentTeam } from '@/hooks/use-current-team';
import { useStatePing } from '@/hooks/use-store-ping';
import { NetworkTopology } from '@/pages/branches/network-topology';
import { index as branches, show as showBranch } from '@/routes/branches';

type DeviceKey = 'vpn' | 'checador' | 'servidor' | 'caja_1' | 'caja_2';
type DeviceNode = {
	key: string;
	label: string;
	ip: string;
	status: 'online' | 'warning' | 'offline' | 'unknown';
	latencyMs?: number | null;
};

interface StoreDetail {
	id: number;
	store_code: string;
	name: string;
	company: number;
	base_ip: string;
	status: 'online' | 'offline' | 'unknown';
	address: string | null;
	city: string | null;
	state: string | null;
	last_seen_at: string | null;
}

interface PingHistoryRow {
	device_label: string;
	is_online: boolean;
	latency_ms: number | null;
	checked_at: string;
}

interface Printer {
	id: number;
	name: string;
	modelo: string | null;
	type_conection: string | null;
}

interface PageProps {
	store: StoreDetail;
	deviceIps: Record<DeviceKey, string>;
	printers: Printer[];
	recentPings: PingHistoryRow[];
}

const DEVICE_ICON: Record<DeviceKey, React.ElementType> = {
	vpn: Router,
	checador: Fingerprint,
	servidor: Server,
	caja_1: CreditCard,
	caja_2: CreditCard,
};

const DEVICE_KEYS = ['vpn', 'checador', 'servidor', 'caja_1', 'caja_2'] satisfies DeviceKey[];

function StatusBadge({ status }: { status: StoreDetail['status'] }) {
	const map = {
		online: {
			label: 'En línea',
			className: 'bg-emerald-500/15 text-emerald-500 border-emerald-500/30',
		},
		offline: {
			label: 'Offline',
			className: 'bg-red-500/15 text-red-500 border-red-500/30',
		},
		unknown: {
			label: 'Unknown',
			className: 'bg-muted text-muted-foreground',
		},
	} as const;
	const { label, className } = map[status];
	return (
		<Badge variant="outline" className={className}>
			{label}
		</Badge>
	);
}

export default function ShowStore({ store, deviceIps, printers, recentPings }: PageProps) {
	const currentTeam = useCurrentTeam();
	const { pingStore, isPinging } = useStatePing(currentTeam.slug);
	const pinging = isPinging(store.id);

	const latestPingsByDevice = recentPings.reduce<Record<string, PingHistoryRow>>((acc, ping) => {
		if (ping.device_label && !acc[ping.device_label]) {
			acc[ping.device_label] = ping;
		}
		return acc;
	}, {});

	function toNode(key: DeviceKey, label: string, ip: string): DeviceNode {
		const result = latestPingsByDevice[key];
		return {
			key,
			label,
			ip,
			status: !result
				? 'unknown'
				: result.is_online
					? result.latency_ms && result.latency_ms > 500
						? 'warning'
						: 'online'
					: 'offline',
			latencyMs: result?.latency_ms ?? undefined,
		};
	}
	return (
		<>
			<Head title={store.name} />

			<div className="flex flex-col gap-6 p-4">
				<div className="flex items-center justify-between">
					<div className="flex items-center gap-3">
						<Button variant="ghost" size="icon" asChild>
							<Link href={branches({ current_team: currentTeam.slug }).url}>
								<ArrowLeft size={16} />
							</Link>
						</Button>
						<div>
							<div className="flex items-center gap-2">
								<h1 className="text-xl font-semibold">{store.name}</h1>
								<StatusBadge status={store.status} />
							</div>
							<p className="flex items-center gap-1 text-sm text-muted-foreground">
								{store.store_code}
								{store.city && (
									<>
										<MapPin size={12} className="ml-2" />
										{store.city}, {store.state}
									</>
								)}
							</p>
						</div>
					</div>

					<Button onClick={() => pingStore(store.id)} disabled={pinging}>
						{pinging ? <Loader2 className="animate-spin" size={16} /> : <Radar size={16} />}
						{pinging ? 'Pingueando...' : 'Ping ahora'}
					</Button>
				</div>

				<div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
					{DEVICE_KEYS.map((key) => {
						const Icon = DEVICE_ICON[key];
						const device = deviceIps[key];
						return (
							<Card key={key}>
								<CardHeader className="pb-2">
									<div className="flex items-center gap-2 text-xl font-medium">
										<Icon size={36} className="text-muted-foreground" />
										{key}
									</div>
								</CardHeader>
								<CardContent>
									<code className="text-xs text-muted-foreground">{device}</code>
								</CardContent>
							</Card>
						);
					})}
					<Card>
						<CardHeader>
							<CardTitle className="flex items-center gap-2 text-xl font-medium">
								<Printer size={36} className="text-muted-foreground" />
								Impresoras
							</CardTitle>
						</CardHeader>
						<CardContent className="space-y-2">
							{printers.map((printer) => (
								<div key={printer.id} className="flex items-center justify-between text-sm">
									<div>
										<p className="font-medium">{printer.name}</p>
										<p className="text-xs text-muted-foreground">
											{printer.modelo} ·{printer.type_conection}
											{printer.type_conection === 'USB' && ' (vía servidor local)'}
										</p>
									</div>
								</div>
							))}
						</CardContent>
					</Card>
				</div>
				<div className="grid grid-cols-1 gap-4 sm:grid-cols-1 lg:grid-cols-2">
					<NetworkTopology
						vpn={toNode('vpn', 'Router/VPN', deviceIps.vpn)}
						checador={toNode('checador', 'Checador', deviceIps.checador)}
						servidor={toNode('servidor', 'Servidor', deviceIps.servidor)}
						caja1={toNode('caja_1', 'Caja 01', deviceIps.caja_1)}
						caja2={toNode('caja_2', 'Caja 02', deviceIps.caja_2)}
					/>
					<Card>
						<CardHeader>
							<CardTitle className="text-sm">Historial reciente de pings</CardTitle>
						</CardHeader>
						<CardContent>
							{recentPings.length === 0 ? (
								<p className="text-sm text-muted-foreground">Sin registros todavía.</p>
							) : (
								<div className="divide-y rounded-md border">
									{recentPings.map((row, i) => (
										<div key={i} className="flex items-center px-4 py-3">
											<div className="flex min-w-[220px] items-center gap-2">
												{row.is_online ? (
													<Wifi size={22} className="text-emerald-500" />
												) : (
													<WifiOff size={22} className="text-red-500" />
												)}
												<span className="truncate font-medium">{row.device_label}</span>
											</div>
											<div className="flex flex-1 items-center gap-3">
												<Progress value={row.latency_ms} className="flex-1" />
												<span className="w-14 text-right text-sm font-medium">
													{row.latency_ms} ms
												</span>
											</div>
											<div className="w-44 text-right text-xs text-muted-foreground">
												{new Date(row.checked_at).toLocaleString('es-MX')}
											</div>{' '}
										</div>
									))}
								</div>
							)}
						</CardContent>
					</Card>
				</div>
			</div>
		</>
	);
}

ShowStore.layout = (props: { currentTeam?: { slug: string } | null; store: StoreDetail }) => ({
	breadcrumbs: [
		{
			title: 'Sucursales',
			href: props.currentTeam ? branches(props.currentTeam.slug) : '/',
		},
		{
			title: props.store.name,
			href: props.currentTeam
				? showBranch({
						current_team: props.currentTeam.slug,
						branch: props.store.id,
					}).url
				: '/',
		},
	],
});
