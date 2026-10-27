<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * App\Models\Membership.
 *
 * @description Representa el vínculo entre una persona y un equipo, con el rol asignado en ese equipo.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador incremental de la membresía.
 * @property int $team_id Identificador del equipo donde participa la persona.
 * @property int $user_id Identificador de la persona miembro.
 * @property TeamRole $role Rol de la persona dentro del equipo.
 * @property Carbon|null $created_at Fecha de creación de la membresía.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Team $team Equipo asociado a la membresía.
 * @property-read User $user Persona asociada a la membresía.
 *
 * Base de datos: pivote incremental `team_members`, único por equipo y usuario.
 * Relaciones: `team` y `user` apuntan a los registros vinculados.
 */
#[Fillable(['team_id', 'user_id', 'role'])]
class Membership extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'team_members';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the team that the membership belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that belongs to this membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }
}
