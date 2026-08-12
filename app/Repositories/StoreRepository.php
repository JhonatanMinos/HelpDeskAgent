<?php

namespace App\Repositories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

class StoreRepository
{
    private const REGIONS = [
        'north' => [
            'Baja California',
            'Baja California Sur',
            'Sonora',
            'Chihuahua',
            'Coahuila',
            'Nuevo León',
            'Tamaulipas',
            'Durango',
            'Sinaloa',
            'Zacatecas',
            'San Luis Potosí',
        ],
        'south' => [
            'Aguascalientes',
            'Colima',
            'Jalisco',
            'Michoacán',
            'Guanajuato',
            'Querétaro',
            'Hidalgo',
            'Estado de México',
            'Ciudad de México',
            'Morelos',
            'Tlaxcala',
            'Puebla',
            'Veracruz',
            'Guerrero',
            'Oaxaca',
            'Chiapas',
            'Tabasco',
            'Campeche',
            'Yucatán',
            'Quintana Roo',
            'Nayarit',
        ],
    ];

    public function getAll(?string $search = null)
    {
        return Store::query()
            ->with('device')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('store_code', 'like', "%{$search}%");
            })
            ->paginate(8)
            ->withQueryString();
    }

    public function getDashboard(): array
    {
        return [
            'total' => Store::count(),
            'online' => Store::where('status', 'online')->count(),
            'regions' => $this->countByCompany(),
            'states' => $this->countByStates(),
        ];
    }

    private function countByCompany(): Collection
    {
        return Store::where('company', 24)->count();
    }

    private function countByStates(): Collection
    {
        return Store::query()
            ->selectRaw('state, COUNT(*) as total')
            ->groupBy('state')
            ->orderByDesc('total')
            ->get();
    }
}
