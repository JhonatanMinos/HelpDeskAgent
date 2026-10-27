<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'pingable_type',
    'pingable_id',
    'ip',
    'device_label',
    'is_online',
    'latency_ms',
    'checked_at',
])]
class PingLog extends Model
{
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function pingable(): MorphTo
    {
        return $this->MorphTo();
    }
}
