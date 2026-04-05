<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        Setting::set($key, $value, $group, $type);
    }

    public function getGroup(string $group): array
    {
        return Setting::getGroup($group);
    }

    public function updateMany(array $settings, string $group): void
    {
        foreach ($settings as $key => $value) {
            $existing = Setting::where('key', $key)->first();
            $type = $existing?->type ?? 'string';
            Setting::set($key, $value, $group, $type);
        }

        Cache::forget('app_settings');
    }
}
