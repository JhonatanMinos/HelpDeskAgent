<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\TelegramMessage.
 *
 * @description Conserva el contenido de un mensaje y su rol dentro de una conversación de Telegram.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador del mensaje.
 * @property int $telegram_conversation_id Identificador de la conversación contenedora.
 * @property string $role Rol del emisor definido por el esquema.
 * @property string $content Contenido textual del mensaje.
 * @property Carbon|null $created_at Fecha de creación del registro.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read TelegramConversation $conversation Conversación a la que pertenece el mensaje.
 */
#[Fillable(['telegram_conversation_id', 'role', 'content'])]
class TelegramMessage extends Model
{
    /** @return BelongsTo<TelegramConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TelegramConversation::class, 'telegram_conversation_id');
    }
}
