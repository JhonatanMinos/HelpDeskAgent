<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\NetworkDiagnosticService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshStoreStatusJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 15;

    public int $uniqueFor = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Store $store)
    {
        //
    }

    public function uniqueId(): string
    {
        return 'store-status-'.$this->store->id;
    }

    public function handle(NetworkDiagnosticService $diagnostics): void
    {
        $diagnostics->pingStore($this->store);
    }
}
