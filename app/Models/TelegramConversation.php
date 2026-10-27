<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\TelegramConversation.
 *
 * @description Agrupa mensajes recibidos en una conversación identificada por un chat de Telegram y opcionalmente una tienda.
 *
 * Base de datos: `last_message_at` es nullable y se convierte a fecha y hora; `last_seen_at` no existe en las migraciones actuales.
 * Relaciones: contiene mensajes y puede apuntar a una tienda.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador de la conversación.
 * @property string $telegram_chat_id Identificador único del chat externo.
 * @property string|null $customer_name Nombre de la persona cliente, si se conoce.
 * @property int|null $store_id Tienda relacionada, si se asignó.
 * @property string $status Estado de conversación definido por el esquema.
 * @property Carbon|null $last_message_at Fecha del último mensaje registrado.
 * @property Carbon|null $created_at Fecha de creación de la conversación.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Collection<int, TelegramMessage> $messages Mensajes de la conversación.
 * @property-read Store|null $store Tienda asociada, si existe.
 */
#[Fillable([
    'telegram_chat_id',
    'customer_name',
    'store_id',
    'status',
    'last_seen_at',
    'last_message_at',
])]
class TelegramConversation extends Model
{
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /** @return HasMany<TelegramMessage, $this> */
    public function messages(): HasMany
    {
        // orderBy aquí, no en el modelo, para que ->latest() en el
        // orquestador siga funcionando sin pisarse con un default order.
        return $this->hasMany(TelegramMessage::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
