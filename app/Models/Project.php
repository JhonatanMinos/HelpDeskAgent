<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Project.
 *
 * @description Agrupa requisitos, tareas y tickets de un proyecto perteneciente a un equipo.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador del proyecto.
 * @property int $team_id Identificador del equipo propietario.
 * @property int $created_by Identificador de la persona creadora.
 * @property string $name Nombre del proyecto.
 * @property string|null $Initial_requirements Requisitos iniciales registrados para el proyecto.
 * @property string $status Estado definido por el esquema del proyecto.
 * @property Carbon|null $estimated_date_delivery Fecha estimada de entrega.
 * @property Carbon|null $created_at Fecha de creación del proyecto.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Collection<int, BoardColumn> $columns Columnas ordenadas del tablero.
 * @property-read Collection<int, Task> $task Tareas asociadas al proyecto.
 * @property-read Collection<int, Ticket> $tickets Tickets asociados al proyecto.
 * @property-read User|null $creator Persona que creó el proyecto.
 */
#[Fillable(['team_id', 'created_by', 'name', 'Initial_requirements', 'status', 'estimated_date_delivery'])]
class Project extends Model
{
    protected function casts(): array
    {
        return ['estimated_date_delivery' => 'date'];
    }

    /** @return HasMany<BoardColumn, $this> */
    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class)->orderBy('order');
    }

    /** @return HasMany<Task, $this> */
    public function task(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seedDefaultColumns(): void
    {
        foreach (['Backlog', 'En progreso', 'Revisión', 'Hecho'] as $i => $nombre) {
            $this->columns()->create(['name' => $nombre, 'order' => $i]);
        }
    }
}
