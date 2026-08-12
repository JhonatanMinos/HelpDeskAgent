<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotOrchestrator;

use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotOrchestrator $orchestrator): Response
    {
        // Telegram manda este header en cada request si configuraste
        // secret_token en setWebhook — verifica que nadie más te esté
        // pegando a este endpoint público simulando ser Telegram.
        if ($request->header('X-Telegram-Bot-Api-Secret-Token') !== env('TELEGRAM_WEBHOOK_SECRET')) {
            return response('Forbidden', 403);
        }

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
