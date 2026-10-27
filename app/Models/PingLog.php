<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\PingLog.
 *
 * @description Conserva el resultado y el momento de una comprobación de conectividad sobre un elemento polimórfico.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador del registro de diagnóstico.
 * @property string|null $pingable_type Clase del elemento comprobado.
 * @property int|null $pingable_id Identificador del elemento comprobado.
 * @property string $ip Dirección consultada.
 * @property string|null $device_label Etiqueta del dispositivo consultado.
 * @property bool $is_online Indica si el dispositivo respondió.
 * @property int|null $latency_ms Latencia medida en milisegundos, si hubo respuesta.
 * @property Carbon $checked_at Fecha y hora de la comprobación.
 * @property Carbon|null $created_at Fecha de creación del registro.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Model|null $pingable Elemento asociado mediante la relación polimórfica.
 */
#[Fillable([
    'pingable_type',
    'pingable_id',
    'ip',
    'device_label',
    'is_online',
    'latency_ms',
    'checked_at',
])]
class PingLog extends Model
{
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function pingable(): MorphTo
    {
        return $this->MorphTo();
    }
}
