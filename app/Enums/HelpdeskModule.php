<?php

namespace App\Enums;

enum HelpdeskModule: string
{
    case Infraestructura = 'Infraestructura'; // tiendas, impresoras, server, ping
    case Dev = 'dev';

    public function label(): string
    {
        return match ($this) {
            self::Infraestructura => 'Infraestructura y Red',
            self::Dev => 'Proyectos Dev',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Infraestructura => 'Network',
            self::Dev => 'KanbanSquare',
        };
    }
}
