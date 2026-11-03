<?php

use App\Jobs\RefreshStoreStatusJob;
use App\Models\Team;
use App\Services\StoreService;
use Illuminate\Support\Facades\Queue;

test('refresh dispatch suppresses duplicates per store until the unique lock expires', function () {
    config(['cache.default' => 'array']);
    Queue::fake();
    $stores = collect(['First', 'Second'])->map(fn ($name) => Team::factory()->create()->stores()->create([
        'store_code' => $name, 'name' => $name, 'company' => 16,
        'base_ip' => '192.168.1.100', 'address' => '1 Main Street',
        'neighborhood' => 'Center', 'city' => 'Example City',
        'postal_code' => '12345', 'state' => 'Example State',
    ]));
    [$first, $second] = $stores->all();
    $service = app(StoreService::class);

    $service->refreshStatusNow($first->id);
    $service->refreshStatusNow($first->id);
    $service->refreshStatusNow($second->id);

    Queue::assertPushed(RefreshStoreStatusJob::class, 2);
    Queue::assertPushed(RefreshStoreStatusJob::class, fn ($job) => $job->store->is($first));
    Queue::assertPushed(RefreshStoreStatusJob::class, fn ($job) => $job->store->is($second));

    $this->travel(61)->seconds();
    $service->refreshStatusNow($first->id);
    Queue::assertPushed(RefreshStoreStatusJob::class, 3);
});
