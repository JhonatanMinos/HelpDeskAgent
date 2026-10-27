<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string|\Closure|Exists>> */
    public function rules(): array
    {
        $teamId = $this->route('current_team')?->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', 'required', 'in:red,hardware,software,impresoras,usuarios,seguridad,configuracion'],
            'priority' => ['sometimes', 'required', 'in:baja,media,alta'],
            'status' => ['sometimes', 'required', 'in:abierto,en_proceso,esperando_cliente,resuelto,cerrado'],
            'store_id' => [
                'nullable',
                Rule::exists('stores', 'id')->where(fn ($query) => $teamId ? $query->where('team_id', $teamId) : $query),
            ],
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where(fn ($query) => $teamId ? $query->where('team_id', $teamId) : $query),
            ],
            'assigned_to' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($teamId) {
                    if ($value === null || $teamId === null) {
                        return;
                    }

                    $team = $this->route('current_team');
                    if ($team && ! $team->members()->whereKey($value)->exists()) {
                        $fail('El usuario asignado debe pertenecer al equipo actual.');
                    }
                },
            ],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
