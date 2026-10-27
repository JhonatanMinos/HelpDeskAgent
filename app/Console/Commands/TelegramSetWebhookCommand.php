<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:set-webhook')]
#[Description('Registra la URL del webhook ante la API de Telegram (requiere Telegram habilitado)')]
class TelegramSetWebhookCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        if (config('services.telegram.enabled') !== true) {
            $this->error('Telegram está deshabilitado. No se registró ningún webhook.');

            return self::FAILURE;
        }

        $url = route('telegram.webhook'); // debe ser HTTPS público
        $secret = config('services.telegram.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            $this->error('Falta configurar TELEGRAM_WEBHOOK_SECRET.');

            return self::FAILURE;
        }

        $success = $telegram->setWebhook($url, $secret);

        $success
            ? $this->info("Webhook registrado: {$url}")
            : $this->error('No se pudo registrar el webhook.');

        return $success ? self::SUCCESS : self::FAILURE;
    }
}
