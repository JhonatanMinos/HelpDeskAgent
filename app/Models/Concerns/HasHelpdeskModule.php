<?php

namespace App\Models\Concerns;

use App\Enums\HelpdeskModule;

trait HasHelpdeskModule
{
    protected function casts(): array
    {
        return array_merge(parent::casts() ?? [], [
            'enabled_modules' => 'array',
            'personal_team' => 'boolean',
        ]);
    }

    public function hasModule(HelpdeskModule $module): bool
    {
        return in_array($module->value, $this->enabled_modules ?? [], true);
    }

    public function enableModule(HelpdeskModule $module): void
    {
        $modules = collect($this->enabled_modules ?? [])
            ->push($module->value)
            ->unique()
            ->values()
            ->all();
        $this->update(['enabled_modules' => $modules]);
    }

    public function disableModule(HelpdeskModule $module): void
    {
        $modules = collect($this->enabled_modules ?? [])
            ->reject(fn($m) => $m === $module->value)
            ->values()
            ->all();

        $this->update(['enabled_modules' => $modules]);
    }

    public function activeModules(): array
    {
        return collect($this->enabled_modules ?? [])
            ->map(fn($value) => HelpdeskModule::from($value))
            ->all();
    }
}
