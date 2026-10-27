<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotOrchestrator $orchestrator): Response
    {
        $message = $request->input('message');

        if (! $message || ! isset($message['text'])) {
            return response('ok', 200); // stickers, fotos, etc. — ignorados por ahora
        }

        $orchestrator->handleIncomingMessage(
            chatId: (string) $message['chat']['id'],
            messageText: $message['text'],
            customerName: $message['from']['first_name'] ?? null,
        );

        return response('ok', 200);
    }
}
