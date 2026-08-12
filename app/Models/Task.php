<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'boards_column_id', 'assigned_to', 'title', 'description', 'priority', 'order', 'limit_date'])]
class Task extends Model
{
    protected function casts(): array
    {
        return ['limit_date' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'boards_column_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
