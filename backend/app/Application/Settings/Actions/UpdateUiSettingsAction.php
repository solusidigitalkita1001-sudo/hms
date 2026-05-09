<?php

namespace App\Application\Settings\Actions;

use App\Domain\Setting\Models\Setting;
use Illuminate\Support\Facades\DB;

class UpdateUiSettingsAction
{
    public function handle(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $settings = [];

            foreach ($payload as $key => $value) {
                $setting = Setting::query()->updateOrCreate(
                    [
                        'property_id' => null,
                        'setting_group' => 'ui',
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    ],
                );

                $settings[$setting->setting_key] = $setting->setting_value;
            }

            return $settings;
        });
    }
}
