<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhook
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('services.telegram.enabled') === true, 404);

        $secret = config('services.telegram.webhook_secret');
        $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        abort_unless(
            is_string($secret) && $secret !== '' && is_string($providedSecret)
                && hash_equals($secret, $providedSecret),
            403,
        );

        return $next($request);
    }
}
