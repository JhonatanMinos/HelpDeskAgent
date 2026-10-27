<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_id',
    'name',
    'modelo',
    'ip_device',
    'type_conection',
    'shared_server',
])]
class Device extends Model
{
    protected function casts(): array
    {
        return [
            'compartida_desde_servidor' => 'boolean',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
