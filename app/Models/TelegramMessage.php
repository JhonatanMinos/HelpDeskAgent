<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['telegram_conversation_id', 'role', 'content'])]
class TelegramMessage extends Model
{
    /** @return BelongsTo<TelegramConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TelegramConversation::class, 'telegram_conversation_id');
    }
}
