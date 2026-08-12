<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Vite manifest exists in tests to avoid ViteException during view rendering.
        $buildPath = base_path('public/build');
        if (! is_dir($buildPath)) {
            mkdir($buildPath, 0777, true);
        }

        $manifestFile = $buildPath.'/manifest.json';
        if (! file_exists($manifestFile)) {
            file_put_contents($manifestFile, json_encode(new \stdClass()));
        }
    }

    protected function tearDown(): void
    {
        // Optionally keep manifest to speed up subsequent tests.
        parent::tearDown();
    }
}
