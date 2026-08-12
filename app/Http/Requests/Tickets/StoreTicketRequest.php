<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teamId = $this->route('current_team')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:red,hardware,software,impresoras,usuarios,seguridad,configuracion'],
            'priority' => ['required', 'in:baja,media,alta'],
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
                    if ($team && !$team->members()->whereKey($value)->exists()) {
                        $fail('El usuario asignado debe pertenecer al equipo actual.');
                    }
                },
            ],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
