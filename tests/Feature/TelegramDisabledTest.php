<?php

use App\Http\Middleware\VerifyTelegramWebhook;
use App\Services\TelegramBotOrchestrator;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Env::getRepository()->set('TELEGRAM_BOT_TOKEN', 'test-token');
    Env::getRepository()->set('TELEGRAM_WEBHOOK_SECRET', 'test-secret');
    config([
        'services.telegram.enabled' => false,
        'services.telegram.bot_token' => 'test-token',
        'services.telegram.webhook_secret' => 'test-secret',
    ]);
    Http::fake();
    $this->app->instance(TelegramService::class, new TelegramService('test-token'));
    $this->app->bind(TelegramBotOrchestrator::class, function () {
        throw new LogicException('Disabled or unauthenticated Telegram must not resolve its orchestrator.');
    });
});

test('disabled Telegram rejects webhook input before resolving incomplete services', function () {
    $this->postJson(route('telegram.webhook'), [
        'message' => ['chat' => ['id' => 42], 'text' => 'Hello'],
    ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->assertNotFound();

    $this->assertDatabaseCount('telegram_conversations', 0);
    $this->assertDatabaseCount('telegram_messages', 0);
    Http::assertNothingSent();
});

test('disabled Telegram does not send messages or register webhooks', function () {
    $telegram = $this->app->make(TelegramService::class);

    expect($telegram->sendMessage('42', 'Hello'))->toBeFalse()
        ->and($telegram->setWebhook('https://example.test/api/webhooks/telegram', 'test-secret'))->toBeFalse();
    Http::assertNothingSent();
});

test('webhook registration explains that Telegram is disabled and makes no requests', function () {
    $this->artisan('telegram:set-webhook')
        ->expectsOutputToContain('Telegram está deshabilitado. No se registró ningún webhook.')
        ->assertExitCode(Command::FAILURE);

    Http::assertNothingSent();
});

test('enabled webhook rejects missing and invalid secrets before resolving its orchestrator', function () {
    config(['services.telegram.enabled' => true, 'services.telegram.webhook_secret' => '']);
    $this->postJson(route('telegram.webhook'))->assertForbidden();

    config(['services.telegram.webhook_secret' => 'test-secret']);
    $this->postJson(route('telegram.webhook'), [], [
        'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
    ])->assertForbidden();

    Http::assertNothingSent();
});

test('enabled authenticated webhook preserves message handling and responds ok', function () {
    config(['services.telegram.enabled' => true]);
    $orchestrator = Mockery::mock(TelegramBotOrchestrator::class);
    $orchestrator->shouldReceive('handleIncomingMessage')->once()->with('42', 'Hello', 'Alex');
    $this->app->instance(TelegramBotOrchestrator::class, $orchestrator);

    $payload = ['message' => [
        'chat' => ['id' => 42],
        'text' => 'Hello',
        'from' => ['first_name' => 'Alex'],
    ]];
    $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'];
    $this->postJson(route('telegram.webhook'), $payload, $headers)->assertOk()->assertSee('ok');

    config(['services.telegram.enabled' => false]);
    $this->postJson(route('telegram.webhook'), $payload, $headers)->assertNotFound();

    Http::assertNothingSent();
});

test('enabled webhook still ignores updates without text', function () {
    config(['services.telegram.enabled' => true]);
    $orchestrator = Mockery::mock(TelegramBotOrchestrator::class);
    $orchestrator->shouldNotReceive('handleIncomingMessage');
    $this->app->instance(TelegramBotOrchestrator::class, $orchestrator);

    $this->postJson(route('telegram.webhook'), [], [
        'X-Telegram-Bot-Api-Secret-Token' => 'test-secret',
    ])->assertOk()->assertSee('ok');
});

test('explicitly enabled outbound messages retain their API payload', function () {
    config(['services.telegram.enabled' => true]);

    expect($this->app->make(TelegramService::class)->sendMessage('42', 'Hello'))->toBeTrue();
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
        && $request['chat_id'] === '42'
        && $request['text'] === 'Hello'
        && $request['parse_mode'] === 'Markdown');
});

test('explicitly enabled webhook registration retains its command feedback and API payload', function () {
    config(['services.telegram.enabled' => true]);
    $url = route('telegram.webhook');

    $this->artisan('telegram:set-webhook')
        ->expectsOutputToContain("Webhook registrado: {$url}")
        ->assertExitCode(Command::SUCCESS);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/bottest-token/setWebhook'
        && $request['url'] === $url
        && $request['secret_token'] === 'test-secret');
});

test('enabled webhook registration refuses missing credentials without contacting Telegram', function () {
    config(['services.telegram.enabled' => true, 'services.telegram.webhook_secret' => '']);

    $this->artisan('telegram:set-webhook')
        ->expectsOutputToContain('Falta configurar TELEGRAM_WEBHOOK_SECRET.')
        ->assertExitCode(Command::FAILURE);

    Http::assertNothingSent();
});

test('enabled Telegram without a bot token makes no outbound requests', function () {
    config(['services.telegram.enabled' => true, 'services.telegram.bot_token' => '']);
    $telegram = new TelegramService;

    expect($telegram->sendMessage('42', 'Hello'))->toBeFalse()
        ->and($telegram->setWebhook('https://example.test/api/webhooks/telegram', 'test-secret'))->toBeFalse();
    Http::assertNothingSent();
});

test('rejected Telegram requests preserve all data and dispatch no jobs', function () {
    Queue::fake();
    $stateHash = function (): string {
        $state = [];

        foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name") as $table) {
            $state[$table->name] = DB::table($table->name)->get()->toArray();
        }

        return hash('sha256', serialize($state));
    };

    foreach ([[false, '', 404], [true, '', 403], [true, 'test-secret', 403]] as [$enabled, $secret, $status]) {
        config(['services.telegram.enabled' => $enabled, 'services.telegram.webhook_secret' => $secret]);
        $before = $stateHash();

        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => 42], 'text' => 'Do not process'],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret'])->assertStatus($status);

        expect($stateHash())->toBe($before);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }
});

test('compiled webhook routes retain the Telegram guard', function () {
    $routes = Route::getRoutes()->compile();

    expect($routes['attributes']['telegram.webhook']['action']['middleware'])
        ->toContain(VerifyTelegramWebhook::class);
});
