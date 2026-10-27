<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
