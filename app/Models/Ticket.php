<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $due_date
 * @property Carbon|null $resolved_at
 */
#[Fillable(['team_id', 'created_by', 'assigned_to', 'store_id', 'project_id', 'title', 'description', 'category', 'priority', 'status', 'due_date', 'resolved_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<TicketComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    /** @return HasMany<TicketActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->latest();
    }
}
