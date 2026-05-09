<?php

namespace App\Application\Settings\Services;

use App\Domain\Setting\Models\Setting;

class SettingsService
{
    public function grouped(): array
    {
        return Setting::query()
            ->orderBy('setting_group')
            ->orderBy('setting_key')
            ->get()
            ->groupBy('setting_group')
            ->map(fn ($settings) => $settings->mapWithKeys(
                fn (Setting $setting): array => [$setting->setting_key => $setting->setting_value]
            ))
            ->toArray();
    }
}
