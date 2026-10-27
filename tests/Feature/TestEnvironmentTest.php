<?php

use App\Models\User;
use Illuminate\Foundation\Vite;

test('feature tests start with an isolated database', function () {
    $this->assertDatabaseCount('users', 0);

    User::factory()->create();

    $this->assertDatabaseCount('users', 1);
});

test('feature tests do not inherit data from another test', function () {
    $this->assertDatabaseCount('users', 0);

    User::factory()->create();

    $this->assertDatabaseCount('users', 1);
});

test('feature views do not require generated Vite assets', function () {
    expect((string) app(Vite::class)(['resources/js/app.tsx']))->toBe('');
});
