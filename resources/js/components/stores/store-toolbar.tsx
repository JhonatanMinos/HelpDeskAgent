import { Loader2, RefreshCw, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type StoreToolbarProps = {
    search: string;
    onSearchChange: (value: string) => void;
    onRefresh: () => void;
    refreshing: boolean;
};

export function StoreToolbar({
    search,
    onSearchChange,
    onRefresh,
    refreshing,
}: StoreToolbarProps) {
    return (
        <div className="flex items-center justify-between gap-4">
            <div className="relative w-full max-w-sm">
                <Search
                    size={16}
                    className="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    value={search}
                    onChange={(e) => onSearchChange(e.target.value)}
                    placeholder="Buscar sucursal..."
                    className="pl-9"
                />
            </div>

            <Button
                onClick={onRefresh}
                disabled={refreshing}
                variant="outline"
                size="sm"
            >
                {refreshing ? (
                    <Loader2 className="animate-spin" size={16} />
                ) : (
                    <RefreshCw size={16} />
                )}
                {refreshing ? 'Actualizando...' : 'Actualizar estatus'}
            </Button>
        </div>
    );
}
