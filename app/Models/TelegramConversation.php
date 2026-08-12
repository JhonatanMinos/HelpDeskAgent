<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Datebase\Eloquent\Relations\BelongsTo;

#[Fillable([
    'telegram_chat_id',
    'customer_name',
    'store_id',
    'status',
    'last_seen_at',
    'last_message_at',
])]
class TelegramConversation extends Model
{

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        // orderBy aquí, no en el modelo, para que ->latest() en el
        // orquestador siga funcionando sin pisarse con un default order.
        return $this->hasMany(TelegramMessage::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
