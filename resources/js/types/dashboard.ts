export type DashboardOverview = {
	branches: { total: number; online: number; offline: number; unknown: number };
	attention: {
		id: number;
		code: string;
		name: string;
		city: string;
		state: string;
		status: 'offline' | 'unknown';
		lastCheckedAt: string | null;
	}[];
	tickets: Record<'abierto' | 'en_proceso' | 'esperando_cliente' | 'resuelto' | 'cerrado', number>;
	activity: {
		id: number;
		ticketId: number;
		ticketTitle: string;
		type: string;
		createdAt: string | null;
	}[];
};
