<?php

use App\Services\NetworkDiagnosticService;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $temporaryDirectory = new Process(['mktemp', '-d', sys_get_temp_dir().'/helpdesk-ping-test.XXXXXX']);
    $temporaryDirectory->mustRun();
    $this->pingScratch = trim($temporaryDirectory->getOutput());
    $this->pingPath = [getenv('PATH'), $_ENV['PATH'] ?? null, $_SERVER['PATH'] ?? null];

    file_put_contents($this->pingScratch.'/ping', "#!/bin/sh\nprintf '%s\\n' \"\$@\" > ".escapeshellarg($this->pingScratch.'/arguments')."\nprintf 'time=2.5 ms\\n'\n");
    chmod($this->pingScratch.'/ping', 0755);

    $path = $this->pingScratch.PATH_SEPARATOR.$this->pingPath[0];
    putenv('PATH='.$path);
    $_ENV['PATH'] = $_SERVER['PATH'] = $path;
});

afterEach(function () {
    if (! isset($this->pingPath)) {
        return;
    }

    [$environment, $env, $server] = $this->pingPath;
    putenv($environment === false ? 'PATH' : 'PATH='.$environment);
    if ($env === null) {
        unset($_ENV['PATH']);
    } else {
        $_ENV['PATH'] = $env;
    }
    if ($server === null) {
        unset($_SERVER['PATH']);
    } else {
        $_SERVER['PATH'] = $server;
    }

    File::deleteDirectory($this->pingScratch);
});

test('ping reads runtime configuration and preserves its public result', function () {
    config(['network.ping_timeout_ms' => 4200, 'network.ping_count' => 6]);

    expect((new NetworkDiagnosticService)->pingIp('192.0.2.10'))->toBe(['is_online' => true, 'latency_ms' => 3]);
    expect(file($this->pingScratch.'/arguments', FILE_IGNORE_NEW_LINES))->toBe(['-c', '6', '-W', '5', '192.0.2.10']);
});

test('explicit ping options take precedence over configuration', function () {
    config(['network.ping_timeout_ms' => 4200, 'network.ping_count' => 6]);

    expect((new NetworkDiagnosticService(2500, 4))->pingIp('192.0.2.11'))->toBe(['is_online' => true, 'latency_ms' => 3]);
    expect(file($this->pingScratch.'/arguments', FILE_IGNORE_NEW_LINES))->toBe(['-c', '4', '-W', '3', '192.0.2.11']);
});

test('ping retains its existing defaults', function () {
    config(['network.ping_timeout_ms' => 800, 'network.ping_count' => 2]);

    (new NetworkDiagnosticService)->pingIp('192.0.2.12');

    expect(file($this->pingScratch.'/arguments', FILE_IGNORE_NEW_LINES))->toBe(['-c', '2', '-W', '1', '192.0.2.12']);
});

test('a partial override retains the other configured ping option', function (?int $timeout, ?int $count, array $expected) {
    config(['network.ping_timeout_ms' => 4200, 'network.ping_count' => 6]);

    (new NetworkDiagnosticService($timeout, $count))->pingIp('192.0.2.13');

    expect(file($this->pingScratch.'/arguments', FILE_IGNORE_NEW_LINES))->toBe($expected);
})->with([
    'count only' => [null, 4, ['-c', '4', '-W', '5', '192.0.2.13']],
    'timeout only' => [1500, null, ['-c', '6', '-W', '2', '192.0.2.13']],
]);

test('ping preserves the minimum timeout', function (int $timeout) {
    config(['network.ping_timeout_ms' => $timeout, 'network.ping_count' => 2]);

    (new NetworkDiagnosticService)->pingIp('192.0.2.14');

    expect(file($this->pingScratch.'/arguments', FILE_IGNORE_NEW_LINES))->toBe(['-c', '2', '-W', '1', '192.0.2.14']);
})->with(['zero' => 0, 'negative' => -1]);

test('configured ping preserves parsing and failed-process results', function (string $output, int $exit, array $expected) {
    config(['network.ping_timeout_ms' => 800, 'network.ping_count' => 2]);
    file_put_contents($this->pingScratch.'/ping', "#!/bin/sh\nprintf '%s\\n' ".escapeshellarg($output)."\nexit $exit\n");

    expect((new NetworkDiagnosticService)->pingIp('192.0.2.15'))->toBe($expected);
})->with([
    'less than one millisecond' => ['time<1 ms', 0, ['is_online' => true, 'latency_ms' => 1]],
    'no latency' => ['ok', 0, ['is_online' => true, 'latency_ms' => null]],
    'failed process' => ['time=2.5 ms', 1, ['is_online' => false, 'latency_ms' => null]],
]);
