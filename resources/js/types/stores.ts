export type Store = {
	id: number;
	store_code: string;
	name: string;
	company: number;
	base_ip: string;
	status: 'online' | 'offline' | 'unknown';
	state: string | null;
};
