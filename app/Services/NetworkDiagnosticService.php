<?php

namespace App\Services;

use App\Models\PingLog;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class NetworkDiagnosticService
{
    private readonly int $timeoutMs;

    private readonly int $count;

    public function __construct(?int $timeoutMs = null, ?int $count = null)
    {
        // Se resuelve el valor final ANTES de asignarlo, así la propiedad
        // readonly se asigna una sola vez y nunca se reescribe.
        $this->timeoutMs = $timeoutMs ?? (int) env('PING_TIMEOUT_MS', 800);
        $this->count = $count ?? (int) env('PING_COUNT', 2);
    }

    /**
     * Hace ping a la IP principal de la tienda (ajusta a tu campo real,
     * ej: $store->ip_red o $store->ipForDevice(...) si ya calculas IPs
     * por dispositivo como vimos antes) y actualiza status + last_seen_at.
     *
     * @return array<string, PingLog>
     */
    public function pingStore(Store $store): array
    {
        $results = [];

        foreach ($store->device_ips as $deviceKey => $ip) {
            $result = $this->pingIp($ip);

            $log = $store->pingLogs()->create([
                'ip' => $ip,
                'device_label' => $deviceKey, // o el label bonito si prefieres
                'is_online' => $result['is_online'],
                'latency_ms' => $result['latency_ms'],
                'checked_at' => now(),
            ]);

            $results[$deviceKey] = $log;
        }

        // El status global de la tienda refleja VPN + servidor, que son los
        // dos dispositivos críticos — sin VPN no hay salida a la matriz,
        // sin servidor no hay impresión ni caja funcionando.
        $vpnOk = $results['vpn']->is_online;
        $servidorOk = $results['servidor']->is_online;

        $store->update([
            'status' => ($vpnOk && $servidorOk) ? 'online' : 'offline',
            'last_seen_at' => now(),
        ]);

        return $results;
    }

    /**
     * Ping real vía Symfony\Process (nunca shell_exec/exec crudo, por
     * riesgo de inyección de comandos si algún día la IP viene de input).
     *
     * @return array{is_online: bool, latency_ms: int|null}
     */
    public function pingIp(string $ip): array
    {
        $timeoutSeconds = max(1, (int) ceil($this->timeoutMs / 1000));

        $process = new Process([
            'ping',
            '-c',
            (string) $this->count,
            '-W',
            (string) $timeoutSeconds,
            $ip,
        ]);
        $process->setTimeout($timeoutSeconds * $this->count + 2);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::warning("Ping falló para {$ip}", ['error' => $e->getMessage()]);

            return ['is_online' => false, 'latency_ms' => null];
        }

        $isOnline = $process->isSuccessful();
        $latency = null;

        if ($isOnline && preg_match('/time[=<]([\d.]+)\s*ms/', $process->getOutput(), $m)) {
            $latency = (int) round((float) $m[1]);
        }

        return ['is_online' => $isOnline, 'latency_ms' => $latency];
    }

    /**
     * @return array{servidor_ip: string, servidor_online: bool, compartida_puerto_abierto: bool, protocolo: string, estado: 'servidor_caido'|'compartir_caido'|'ok', checked_at: string}
     */
    public function checkPrintChain(Store $store, string $shareProtocol = 'smb'): array
    {
        $servidorIp = $store->ipForDevice('servidor');
        $port = $shareProtocol === 'cups' ? 631 : 445;

        $servidorPing = $this->pingIp($servidorIp);
        $shareOpen = $this->isPortOpen($servidorIp, $port);

        return [
            'servidor_ip' => $servidorIp,
            'servidor_online' => $servidorPing['is_online'],
            'compartida_puerto_abierto' => $shareOpen,
            'protocolo' => $shareProtocol,
            // Estado interpretado para la UI: si el server responde pero el
            // puerto de compartición no, es la señal más útil para N2/N3 —
            // "el server está vivo pero algo tumbó el servicio de impresión".
            'estado' => match (true) {
                ! $servidorPing['is_online'] => 'servidor_caido',
                ! $shareOpen => 'compartir_caido',
                default => 'ok',
            },
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function isPortOpen(string $ip, int $port, int $timeoutSeconds = 2): bool
    {
        $connection = @fsockopen($ip, $port, $errno, $errstr, $timeoutSeconds);

        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }
}
