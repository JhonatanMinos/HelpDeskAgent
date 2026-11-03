<?php

namespace App\Enums;

enum StoreDevice: string
{
    case Vpn = 'vpn';
    case Checador = 'checador';
    case Servidor = 'servidor';
    case Caja1 = 'caja_1';
    case Caja2 = 'caja_2';

    public function octet(): int
    {
        return match ($this) {
            self::Vpn => 1,
            self::Checador => 107,
            self::Servidor => 100,
            self::Caja1 => 101,
            self::Caja2 => 102,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Vpn => 'VPN (TP-Link)',
            self::Checador => 'Checador ZKTeco',
            self::Servidor => 'Servidor Local',
            self::Caja1 => 'Caja de Cobro 1',
            self::Caja2 => 'Caja de Cobro 2',
        };
    }

    public static function fromOctet(int $octet): ?self
    {
        return collect(self::cases())->first(fn (self $d) => $d->octet() === $octet);
    }
}
