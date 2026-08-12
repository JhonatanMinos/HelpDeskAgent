import { Check, Map as MapIcon, Store } from 'lucide-react';
import {
    Card,
    CardAction,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import 'odometer/themes/odometer-theme-default.css';

type StoreDashboardStats = {
    total: number;
    onlineCount: number;
    regions: { north: number; south: number };
};

export function StoreStatsCards({
    total,
    onlineCount,
    regions,
}: StoreDashboardStats) {
    return (
        <div className="grid grid-cols-3 gap-4 @xl/main:grid-cols-2 @5xl/main:grid-cols-3">
            <Card className="@container/card">
                <CardHeader>
                    <CardDescription>Total de Sucursales</CardDescription>
                    <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                        {total}
                    </CardTitle>
                    <CardAction>
                        <Store size={34} />
                    </CardAction>
                </CardHeader>
            </Card>

            <Card className="@container/card">
                <CardHeader>
                    <CardDescription>Online Status</CardDescription>
                    <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                        {onlineCount} / {total}
                    </CardTitle>
                    <CardAction>
                        <Check size={44} className="text-emerald-500" />
                    </CardAction>
                </CardHeader>
            </Card>

            <Card className="@container/card">
                <CardHeader>
                    <CardDescription>Regiones</CardDescription>
                    <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                        {regions.north} / {regions.south}
                    </CardTitle>
                    <CardAction>
                        <MapIcon size={44} />
                    </CardAction>
                </CardHeader>
            </Card>
        </div>
    );
}
