<?php

namespace App\Console\Commands;

use App\Jobs\RefreshStoreStatusJob;
use App\Models\Store;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('branches:refresh-status')]
#[Description('Despacha el ping de red para todas las sucursales')]
class RefreshBranchesStatusCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Store::query()->chunkById(50, function ($stores) {
            $stores->each(fn (Store $store) => RefreshStoreStatusJob::dispatch($store));
        });

        $this->info('Refresh de status despachado para todas las tiendas.');

        return self::SUCCESS;
    }
}
