import {
	ChevronsLeftRightEllipsis,
	CreditCard,
	Fingerprint,
	Network,
	Router,
	Server,
} from 'lucide-react';

type DeviceStatus = 'online' | 'warning' | 'offline' | 'unknown';

interface DeviceNode {
	key: string;
	label: string;
	ip: string;
	status: DeviceStatus;
	latencyMs?: number | null;
}

interface Props {
	vpn: DeviceNode;
	checador: DeviceNode;
	servidor: DeviceNode;
	caja1: DeviceNode;
	caja2: DeviceNode;
}

const STATUS_COLOR: Record<DeviceStatus, string> = {
	online: '#10b981', // emerald-500
	warning: '#f59e0b', // amber-500
	offline: '#ef4444', // red-500
	unknown: '#6b7280', // gray-500
};

const ICONS: Record<string, React.ElementType> = {
	vpn: Router,
	checador: Fingerprint,
	servidor: Server,
	caja1: CreditCard,
	caja2: CreditCard,
};

// Coordenadas fijas en un viewBox de 700x420 — mismo enfoque que un
// diagrama de red estático: no hace falta layout dinámico si los 5
// dispositivos son siempre los mismos.
const POSITIONS: Record<string, { x: number; y: number }> = {
	vpn: { x: 350, y: 60 },
	switch: { x: 350, y: 180 },
	servidor: { x: 180, y: 300 },
	checador: { x: 510, y: 100 },
	caja1: { x: 350, y: 300 },
	caja2: { x: 530, y: 300 },
};

const NODE_WIDTH = 130;
const NODE_HEIGHT = 90;

function StoreNode({ node }: { node: DeviceNode }) {
	const pos = POSITIONS[node.key];
	const Icon = ICONS[node.key];
	const color = STATUS_COLOR[node.status];

	return (
		<g transform={`translate(${pos.x - NODE_WIDTH / 2}, ${pos.y - NODE_HEIGHT / 2})`}>
			<rect
				width={NODE_WIDTH}
				height={NODE_HEIGHT}
				rx={10}
				fill="var(--card)"
				stroke={node.status === 'offline' ? color : 'var(--border)'}
				strokeWidth={node.status === 'offline' ? 2 : 1}
			/>
			<circle cx={NODE_WIDTH - 12} cy={12} r={5} fill={color} className="animate-pulse" />
			<foreignObject x={12} y={10} width={24} height={24}>
				<Icon size={18} className="text-muted-foreground" />
			</foreignObject>
			<text
				x={NODE_WIDTH / 2}
				y={46}
				textAnchor="middle"
				className="fill-current text-[13px] font-medium"
			>
				{node.label}
			</text>
			<text
				x={NODE_WIDTH / 2}
				y={64}
				textAnchor="middle"
				className="fill-current text-[11px] opacity-60"
			>
				{node.ip}
			</text>
			{node.status === 'offline' && (
				<text
					x={NODE_WIDTH / 2}
					y={80}
					textAnchor="middle"
					fontSize={10}
					fill={color}
					fontWeight={600}
				>
					OFFLINE
				</text>
			)}
			{node.status === 'warning' && node.latencyMs != null && (
				<text
					x={NODE_WIDTH / 2}
					y={80}
					textAnchor="middle"
					fontSize={10}
					fill={color}
					fontWeight={600}
				>
					{node.latencyMs}ms
				</text>
			)}
		</g>
	);
}

function Link({
	from,
	to,
	status,
}: {
	from: keyof typeof POSITIONS;
	to: keyof typeof POSITIONS;
	status: DeviceStatus;
}) {
	const a = POSITIONS[from];
	const b = POSITIONS[to];
	const color = STATUS_COLOR[status];
	const dashed = status === 'offline';

	return (
		<line
			x1={a.x}
			y1={a.y}
			x2={b.x}
			y2={b.y}
			stroke={color}
			strokeWidth={2}
			strokeDasharray={dashed ? '6 4' : undefined}
			opacity={status === 'unknown' ? 0.3 : 0.8}
			className="animate-pulse"
		/>
	);
}

export function NetworkTopology({ vpn, checador, servidor, caja1, caja2 }: Props) {
	return (
		<div className="rounded-xl border bg-card p-4">
			<div className="mb-3 flex items-center justify-between">
				<div className="flex items-center gap-2 text-sm font-medium">
					<Network size={16} className="text-muted-foreground" />
					Topología de Red
				</div>
				<div className="flex items-center gap-3 text-xs text-muted-foreground">
					<span className="flex items-center gap-1">
						<span className="h-2 w-2 rounded-full bg-emerald-500" /> Online
					</span>
					<span className="flex items-center gap-1">
						<span className="h-2 w-2 rounded-full bg-amber-500" /> Alerta
					</span>
					<span className="flex items-center gap-1">
						<span className="h-2 w-2 rounded-full bg-red-500" /> Offline
					</span>
				</div>
			</div>

			<svg viewBox="0 0 700 420" className="h-auto w-full">
				{/* Líneas primero, para que queden debajo de los nodos */}
				<Link from="vpn" to="switch" status={vpn.status} />
				<Link from="switch" to="servidor" status={servidor.status} />
				<Link from="switch" to="checador" status={checador.status} />
				<Link from="switch" to="caja1" status={caja1.status} />
				<Link from="switch" to="caja2" status={caja2.status} />

				{/* Ícono de switch en el centro, sin datos propios */}
				<g transform={`translate(${POSITIONS.switch.x - 16}, ${POSITIONS.switch.y - 16})`}>
					<rect width={32} height={32} rx={6} fill="var(--muted)" />
				</g>

				<StoreNode node={{ ...vpn, key: 'vpn' }} />
				<StoreNode node={{ ...servidor, key: 'servidor' }} />
				<StoreNode node={{ ...checador, key: 'checador' }} />
				<StoreNode node={{ ...caja1, key: 'caja1' }} />
				<StoreNode node={{ ...caja2, key: 'caja2' }} />
			</svg>
		</div>
	);
}
