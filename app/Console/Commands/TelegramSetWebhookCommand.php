<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:set-webhook')]
#[Description('Registra la URL del webhook ante la API de Telegram')]
class TelegramSetWebhookCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram)
    {
        $url = route('telegram.webhook'); // debe ser HTTPS público
        $secret = env('TELEGRAM_WEBHOOK_SECRET');

        $success = $telegram->setWebhook($url, $secret);

        $success
            ? $this->info("Webhook registrado: {$url}")
            : $this->error('No se pudo registrar el webhook.');

        return $success ? self::SUCCESS : self::FAILURE;
    }
}
