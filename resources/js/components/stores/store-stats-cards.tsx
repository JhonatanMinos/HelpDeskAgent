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
    companys: { company: string; total: number };
};

export function StoreStatsCards({
    total,
    onlineCount,
    companys,
}: StoreDashboardStats) {
    console.log(companys);
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
                    <CardDescription>en linea</CardDescription>
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
                    <CardDescription>Compañias</CardDescription>
                    {companys.map((item) => (
                        <CardTitle
                            key={item.companys}
                            className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl"
                        >
                            {item.company} / {item.total}
                        </CardTitle>
                    ))}
                    <CardAction>
                        <MapIcon size={44} />
                    </CardAction>
                </CardHeader>
            </Card>
        </div>
    );
}
