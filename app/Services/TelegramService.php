<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private readonly string $botToken;

    private readonly string $apiUrl;

    public function __construct(?string $botToken = null)
    {
        $this->botToken = $botToken ?: env('TELEGRAM_BOT_TOKEN', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            $response->throw();

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram send failed', ['chat_id' => $chatId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Registra el webhook ante Telegram — se corre UNA vez (o cuando
     * cambie tu URL pública), no en cada request. Ver comando artisan
     * más abajo.
     */
    public function setWebhook(string $url, string $secretToken): bool
    {
        $response = Http::post("{$this->apiUrl}/setWebhook", [
            'url' => $url,
            'secret_token' => $secretToken,
        ]);

        return $response->successful();
    }
}
