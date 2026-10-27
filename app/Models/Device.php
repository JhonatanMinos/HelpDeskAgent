<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Device.
 *
 * @description Describe un dispositivo registrado en una tienda, incluidos su conexión y su IP configurada.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador del dispositivo.
 * @property int $store_id Identificador de la tienda asociada.
 * @property string $name Nombre del dispositivo.
 * @property string|null $modelo Modelo del equipo, cuando se conoce.
 * @property string|null $ip_device Dirección IP configurada para el dispositivo.
 * @property string $type_conection Tipo de conexión, con valores definidos por el esquema.
 * @property bool $shared_server Indica si el dispositivo comparte el servidor.
 * @property Carbon|null $created_at Fecha de creación del registro.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Store $store Tienda a la que pertenece el dispositivo.
 */
#[Fillable([
    'store_id',
    'name',
    'modelo',
    'ip_device',
    'type_conection',
    'shared_server',
])]
class Device extends Model
{
    protected function casts(): array
    {
        return [
            'compartida_desde_servidor' => 'boolean',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
