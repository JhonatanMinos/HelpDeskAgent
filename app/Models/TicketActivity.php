<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\TicketActivity.
 *
 * @description Registra un evento o cambio asociado a un ticket y a la persona que lo realizó.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador de la actividad.
 * @property int $ticket_id Identificador del ticket relacionado.
 * @property int $user_id Identificador de la persona que generó la actividad.
 * @property string $type Tipo de evento registrado.
 * @property string|null $field Campo afectado, si aplica.
 * @property string|null $old_value Valor anterior, si aplica.
 * @property string|null $new_value Valor nuevo, si aplica.
 * @property string|null $note Nota descriptiva opcional.
 * @property Carbon|null $created_at Fecha de creación del evento.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Ticket $ticket Ticket al que pertenece la actividad.
 * @property-read User $user Persona asociada a la actividad.
 */
#[Fillable(['ticket_id', 'user_id', 'type', 'field', 'old_value', 'new_value', 'note'])]
class TicketActivity extends Model
{
    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
