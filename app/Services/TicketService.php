<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    /** @param array<string, mixed> $data */
    public function createTicket(Team $team, array $data, User $creator): Ticket
    {
        $this->validateTeamScopedRelations($team, $data);

        return DB::transaction(function () use ($team, $data, $creator): Ticket {
            $ticket = Ticket::create([
                ...$data,
                'team_id' => $team->id,
                'created_by' => $creator->id,
            ]);

            $this->recordActivity(
                $ticket,
                $creator,
                'created',
                null,
                null,
                'Ticket creado',
            );

            return $ticket;
        });
    }

    /** @param array<string, mixed> $data */
    public function updateTicket(Ticket $ticket, array $data, User $user, Team $team): Ticket
    {
        $this->validateTeamScopedRelations($team, $data);

        return DB::transaction(function () use ($ticket, $data, $user): Ticket {
            $original = $ticket->getOriginal();

            if (array_key_exists('status', $data)) {
                $data['resolved_at'] = in_array($data['status'], ['resuelto', 'cerrado'], true)
                    ? ($ticket->resolved_at ?? now())
                    : null;
            }

            $ticket->fill($data);
            $ticket->save();

            foreach ($ticket->getChanges() as $field => $newValue) {
                if ($field === 'updated_at') {
                    continue;
                }

                $oldValue = $original[$field] ?? null;
                $this->recordActivity(
                    $ticket,
                    $user,
                    'updated',
                    $field,
                    $this->normalizeValue($oldValue),
                    $this->normalizeValue($newValue),
                );
            }

            return $ticket;
        });
    }

    public function addComment(Ticket $ticket, User $user, string $body): TicketComment
    {
        $body = trim($body);

        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'El seguimiento no puede estar vacío.']);
        }

        return DB::transaction(function () use ($ticket, $user, $body): TicketComment {
            $comment = $ticket->comments()->create([
                'user_id' => $user->id,
                'body' => $body,
            ]);

            $this->recordActivity(
                $ticket,
                $user,
                'comment',
                null,
                null,
                $body,
            );

            return $comment;
        });
    }

    public function deleteTicket(Ticket $ticket, User $user): void
    {
        DB::transaction(function () use ($ticket, $user): void {
            // Record deletion activity before removing the ticket so we keep an audit entry
            $this->recordActivity(
                $ticket,
                $user,
                'deleted',
                null,
                null,
                'Ticket eliminado',
            );

            $ticket->delete();
        });
    }

    protected function recordActivity(Ticket $ticket, User $user, string $type, ?string $field, ?string $oldValue, ?string $newValue, ?string $note = null): TicketActivity
    {
        return $ticket->activities()->create([
            'user_id' => $user->id,
            'type' => $type,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'note' => $note,
        ]);
    }

    /** @param array<string, mixed> $data */
    protected function validateTeamScopedRelations(Team $team, array $data): void
    {
        if (array_key_exists('store_id', $data) && $data['store_id'] !== null) {
            if (! $team->stores()->whereKey($data['store_id'])->exists()) {
                throw ValidationException::withMessages(['store_id' => 'La sucursal debe pertenecer al equipo actual.']);
            }
        }

        if (array_key_exists('project_id', $data) && $data['project_id'] !== null) {
            if (! $team->projects()->whereKey($data['project_id'])->exists()) {
                throw ValidationException::withMessages(['project_id' => 'El proyecto debe pertenecer al equipo actual.']);
            }
        }

        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] !== null) {
            if (! $team->members()->whereKey($data['assigned_to'])->exists()) {
                throw ValidationException::withMessages(['assigned_to' => 'El usuario asignado debe ser miembro del equipo actual.']);
            }
        }
    }

    protected function normalizeValue(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $value === null ? null : (string) $value;
    }
}
