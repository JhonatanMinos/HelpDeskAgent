<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Ticket.
 *
 * @description Registra una solicitud de soporte de un equipo, con clasificación, responsables y vínculos opcionales a tienda o proyecto.
 *
 * Traits: usa `HasFactory` con `TicketFactory`.
 * Fechas: `due_date` se trata como fecha y `resolved_at` como fecha y hora.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador del ticket.
 * @property int $team_id Identificador del equipo propietario.
 * @property int $created_by Identificador de la persona creadora.
 * @property int|null $assigned_to Identificador de la persona asignada, si existe.
 * @property int|null $store_id Identificador de la tienda vinculada, si existe.
 * @property int|null $project_id Identificador del proyecto vinculado, si existe.
 * @property string $title Título del ticket.
 * @property string|null $description Descripción proporcionada para el ticket.
 * @property string $category Categoría definida por el esquema.
 * @property string $priority Prioridad definida por el esquema.
 * @property string $status Estado definido por el esquema.
 * @property Carbon|null $due_date Fecha límite del ticket.
 * @property Carbon|null $resolved_at Momento de resolución del ticket.
 * @property Carbon|null $created_at Fecha de creación del ticket.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Team|null $team Equipo propietario del ticket.
 * @property-read Store|null $store Tienda vinculada, si existe.
 * @property-read Project|null $project Proyecto vinculado, si existe.
 * @property-read User|null $creator Persona que creó el ticket.
 * @property-read User|null $assignee Persona asignada, si existe.
 * @property-read Collection<int, TicketComment> $comments Comentarios ordenados de más reciente a más antiguo.
 * @property-read Collection<int, TicketActivity> $activities Actividades ordenadas de más reciente a más antigua.
 */
#[Fillable(['team_id', 'created_by', 'assigned_to', 'store_id', 'project_id', 'title', 'description', 'category', 'priority', 'status', 'due_date', 'resolved_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<TicketComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    /** @return HasMany<TicketActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->latest();
    }
}
