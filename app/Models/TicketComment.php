<?php

namespace App\Models;

use Database\Factories\TicketCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\TicketComment.
 *
 * @description Conserva un comentario escrito por una persona dentro de un ticket.
 *
 * Traits: usa `HasFactory` con `TicketCommentFactory`.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador del comentario.
 * @property int $ticket_id Identificador del ticket comentado.
 * @property int $user_id Identificador de la persona autora.
 * @property string $body Contenido del comentario.
 * @property Carbon|null $created_at Fecha de creación del comentario.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Ticket $ticket Ticket al que pertenece el comentario.
 * @property-read User $user Persona autora del comentario.
 */
#[Fillable(['ticket_id', 'user_id', 'body'])]
class TicketComment extends Model
{
    /** @use HasFactory<TicketCommentFactory> */
    use HasFactory;

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
