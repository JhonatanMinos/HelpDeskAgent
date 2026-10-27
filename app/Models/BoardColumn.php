<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\BoardColumn.
 *
 * @description Representa una columna ordenada del tablero asociado a un proyecto.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador de la columna.
 * @property int $project_id Identificador del proyecto propietario.
 * @property string $name Nombre visible de la columna.
 * @property int $order Posición de la columna dentro del tablero.
 * @property Carbon|null $created_at Fecha de creación del registro.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Project $project Proyecto al que pertenece la columna.
 * @property-read Collection<int, Task> $tasks Tareas ordenadas de esta columna.
 */
#[Fillable(['project_id', 'name', 'order'])]
class BoardColumn extends Model
{
    protected $table = 'boards_columns';

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'boards_column_id')->orderBy('order');
    }
}
