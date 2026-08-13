<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['store_code', 'name', 'company', 'base_ip', 'status', 'last_seen_at', 'team_id', 'address', 'neighborhood', 'city', 'postal_code', 'state', 'lat', 'lng'])]
class Store extends Model
{
    use SoftDeletes;

    private const DEVICE_OCTETS = [
        'vpn' => 1,
        'checador' => 107,
        'servidor' => 100,
        'caja_1' => 101,
        'caja_2' => 102,
    ];

    private const DEVICE_LABELS = [
        'vpn' => 'VPN (TP-Link)',
        'checador' => 'Checador ZKTeco',
        'servidor' => 'Servidor Local',
        'caja_1' => 'Caja de Cobro 1',
        'caja_2' => 'Caja de Cobro 2',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function device(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    private function baseNetworkOctets(): string
    {
        $parts = explode('.', $this->base_ip);
        array_pop($parts); // descarta el último octeto (.100 del servidor)

        return implode('.', $parts);
    }

    public function getDeviceIpsAttribute(): array
    {
        return collect(self::DEVICE_OCTETS)
            ->mapWithKeys(fn ($octet, $key) => [$key => $this->ipForDevice($key)])
            ->all();
    }

    public function ipForDevice(string $deviceKey): string
    {
        if (! array_key_exists($deviceKey, self::DEVICE_OCTETS)) {
            throw new \InvalidArgumentException("Dispositivo desconocido: {$deviceKey}");
        }

        return $this->baseNetworkOctets().'.'.self::DEVICE_OCTETS[$deviceKey];
    }

    public function printFlow(): array
    {
        return [
            'servidor' => $this->ipForDevice('servidor'),
            'cajas' => [
                $this->ipForDevice('caja_1'),
                $this->ipForDevice('caja_2'),
            ],
        ];
    }

    public function pingLogs(): MorphMany
    {
        return $this->morphMany(PingLog::class, 'pingable');
    }
}
