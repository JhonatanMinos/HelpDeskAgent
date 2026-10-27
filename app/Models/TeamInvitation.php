<?php

namespace App\Models;

use App\Enums\TeamRole;
use Database\Factories\TeamInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * App\Models\TeamInvitation.
 *
 * @description Registra una invitación por correo a un equipo, con rol, código de aceptación y fechas de vigencia.
 *
 * Comportamiento: genera un código aleatorio al crear la invitación y expone su ruta mediante ese código.
 * Traits: usa `HasFactory` con `TeamInvitationFactory`.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador de la invitación.
 * @property string $code Código único de aceptación.
 * @property int $team_id Identificador del equipo invitante.
 * @property string $email Correo electrónico de la persona invitada.
 * @property TeamRole $role Rol que se asignará al aceptar.
 * @property int $invited_by Identificador de la persona que envió la invitación.
 * @property Carbon|null $expires_at Fecha de expiración, si se configuró.
 * @property Carbon|null $accepted_at Fecha de aceptación, si fue aceptada.
 * @property Carbon|null $created_at Fecha de creación del registro.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Team $team Equipo al que se refiere la invitación.
 * @property-read User $inviter Persona que envió la invitación.
 */
#[Fillable(['team_id', 'email', 'role', 'invited_by', 'expires_at', 'accepted_at'])]
class TeamInvitation extends Model
{
    /** @use HasFactory<TeamInvitationFactory> */
    use HasFactory;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TeamInvitation $invitation) {
            if (empty($invitation->code)) {
                $invitation->code = Str::random(64);
            }
        });
    }

    /**
     * Get the team that the invitation belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who sent the invitation.
     *
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Determine if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Determine if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->accepted_at === null && ! $this->isExpired();
    }

    /**
     * Determine if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
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
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
