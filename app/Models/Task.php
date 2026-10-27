<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Task.
 *
 * @description Representa una tarea de proyecto ubicada en una columna del tablero, con asignación y prioridad.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador de la tarea.
 * @property int $project_id Identificador del proyecto asociado.
 * @property int $boards_column_id Identificador de la columna actual.
 * @property int|null $assigned_to Identificador de la persona asignada, si existe.
 * @property string $title Título de la tarea.
 * @property string|null $description Descripción de la tarea.
 * @property string $priority Prioridad definida por el esquema.
 * @property int $order Posición de la tarea dentro de la columna.
 * @property Carbon|null $limit_date Fecha límite de la tarea.
 * @property Carbon|null $created_at Fecha de creación del registro.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Project $project Proyecto propietario de la tarea.
 * @property-read BoardColumn $column Columna donde se encuentra la tarea.
 * @property-read User|null $assignee Persona asignada, si existe.
 */
#[Fillable(['project_id', 'boards_column_id', 'assigned_to', 'title', 'description', 'priority', 'order', 'limit_date'])]
class Task extends Model
{
    protected function casts(): array
    {
        return ['limit_date' => 'date'];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return BelongsTo<BoardColumn, $this> */
    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'boards_column_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
