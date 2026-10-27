<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Team.
 *
 * @description Representa un espacio de trabajo con miembros, invitaciones y recursos compartidos.
 *
 * Base de datos: `enabled_modules` es JSON nullable sin cast declarado en este modelo.
 * Comportamiento: genera un slug único al crear el equipo y actualiza el slug cuando cambia el nombre.
 * Traits: `HasFactory`, `SoftDeletes` y `GeneratesUniqueTeamSlugs`.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador del equipo.
 * @property string $name Nombre del equipo.
 * @property string $slug Identificador legible usado para las rutas.
 * @property bool $is_personal Indica si es el equipo personal de una persona.
 * @property string|null $enabled_modules Configuración JSON de módulos habilitados, si está registrada.
 * @property Carbon|null $created_at Fecha de creación del equipo.
 * @property Carbon|null $updated_at Fecha de última actualización.
 * @property Carbon|null $deleted_at Fecha de eliminación lógica.
 *
 * --- Relaciones ---
 * @property-read Collection<int, TeamInvitation> $invitations Invitaciones emitidas para el equipo.
 * @property-read Collection<int, Membership> $memberships Registros de pertenencia al equipo.
 * @property-read Collection<int, User> $members Personas integrantes del equipo.
 * @property-read Collection<int, Store> $stores Tiendas pertenecientes al equipo.
 * @property-read Collection<int, Project> $projects Proyectos pertenecientes al equipo.
 * @property-read Collection<int, Ticket> $tickets Tickets pertenecientes al equipo.
 */
#[Fillable(['name', 'slug', 'is_personal'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name')) {
                $team->slug = static::generateUniqueTeamSlug($team->name, $team->id);
            }
        });
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /** @return HasMany<Store, $this> */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
