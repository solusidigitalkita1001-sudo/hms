<?php

namespace App\Application\Settings\Services;

use App\Domain\Property\Models\Property;
use App\Domain\Setting\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class BusinessDateService
{
    public function currentBusinessDate(Property $property): CarbonImmutable
    {
        $value = $this->businessSettings($property)->get('current_business_date');

        if (is_string($value) && $value !== '') {
            return CarbonImmutable::parse($value, $property->timezone)->startOfDay();
        }

        return CarbonImmutable::now($property->timezone)->startOfDay();
    }

    public function nextBusinessDate(Property $property): CarbonImmutable
    {
        return $this->currentBusinessDate($property)->addDay();
    }

    public function nightAuditCutoffTime(Property $property): string
    {
        $value = $this->businessSettings($property)->get('night_audit_cutoff_time');

        return is_string($value) && $value !== ''
            ? $value
            : '02:00';
    }

    public function snapshot(Property $property): array
    {
        $currentBusinessDate = $this->currentBusinessDate($property);
        $now = CarbonImmutable::now($property->timezone);

        return [
            'property' => [
                'id' => $property->id,
                'code' => $property->code,
                'name' => $property->name,
                'timezone' => $property->timezone,
            ],
            'current_business_date' => $currentBusinessDate->toDateString(),
            'next_business_date' => $currentBusinessDate->addDay()->toDateString(),
            'night_audit_cutoff_time' => $this->nightAuditCutoffTime($property),
            'local_now' => $now->toIso8601String(),
        ];
    }

    public function updateCurrentBusinessDate(Property $property, CarbonImmutable $businessDate): void
    {
        $this->updateBusinessSetting($property, 'current_business_date', $businessDate->toDateString());
    }

    public function updateBusinessSetting(Property $property, string $key, string $value): void
    {
        Setting::query()->updateOrCreate(
            [
                'property_id' => $property->id,
                'setting_group' => 'business',
                'setting_key' => $key,
            ],
            [
                'setting_value' => $value,
            ],
        );
    }

    private function businessSettings(Property $property): Collection
    {
        return Setting::query()
            ->where(function ($query) use ($property): void {
                $query
                    ->where('property_id', $property->id)
                    ->orWhereNull('property_id');
            })
            ->where('setting_group', 'business')
            ->orderByRaw('property_id is null')
            ->get()
            ->mapWithKeys(fn (Setting $setting): array => [$setting->setting_key => $setting->setting_value]);
    }
}
