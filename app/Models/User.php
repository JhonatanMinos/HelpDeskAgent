<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * App\Models\User.
 *
 * @description Representa una cuenta autenticable con datos de verificación de correo, segundo factor y pertenencia a equipos.
 *
 * Traits: `HasFactory`, `HasTeams`, `Notifiable`, `PasskeyAuthenticatable` y `TwoFactorAuthenticatable` aportan las capacidades declaradas por el modelo.
 * Seguridad: los campos de contraseña, segundo factor y token de sesión se excluyen de la serialización.
 *
 * --- Atributos de la base de datos ---
 *
 * @property int $id Identificador de la cuenta.
 * @property string $name Nombre visible de la persona.
 * @property string $email Correo electrónico de acceso.
 * @property Carbon|null $email_verified_at Fecha de verificación del correo.
 * @property string $password Contraseña almacenada en formato protegido.
 * @property string|null $two_factor_secret Secreto de segundo factor, si está configurado.
 * @property string|null $two_factor_recovery_codes Códigos de recuperación del segundo factor.
 * @property Carbon|null $two_factor_confirmed_at Fecha de confirmación del segundo factor.
 * @property string|null $remember_token Token de sesión persistente.
 * @property int|null $current_team_id Identificador del equipo actual, si se seleccionó.
 * @property Carbon|null $created_at Fecha de creación de la cuenta.
 * @property Carbon|null $updated_at Fecha de última actualización.
 *
 * --- Relaciones ---
 * @property-read Team|null $currentTeam Equipo actual de la persona.
 * @property-read Collection<int, Team> $ownedTeams Equipos donde tiene rol de propietario.
 * @property-read Collection<int, Membership> $teamMemberships Registros de pertenencia a equipos.
 * @property-read Collection<int, Team> $teams Equipos de los que forma parte.
 */
#[Fillable(['name', 'email', 'password', 'current_team_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     * ,
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
