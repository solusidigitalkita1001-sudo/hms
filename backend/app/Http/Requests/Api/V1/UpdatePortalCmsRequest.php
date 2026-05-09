<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortalCmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_code' => ['required', 'string', 'max:20'],
            'announcement_badge' => ['required', 'string', 'max:120'],
            'announcement_text' => ['required', 'string', 'max:255'],
            'announcement_link_label' => ['required', 'string', 'max:120'],
            'announcement_link_url' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['required', 'string', 'max:800'],
            'hero_image_url' => ['nullable', 'string', 'max:500'],
            'hero_search_destination_label' => ['required', 'string', 'max:120'],
            'hero_search_destination_value' => ['required', 'string', 'max:255'],
            'hero_search_date_label' => ['required', 'string', 'max:120'],
            'hero_search_date_value' => ['required', 'string', 'max:120'],
            'hero_search_room_label' => ['required', 'string', 'max:120'],
            'hero_search_room_value' => ['required', 'string', 'max:120'],
            'hero_search_button_label' => ['required', 'string', 'max:120'],
            'destinations_title' => ['required', 'string', 'max:255'],
            'explore_title' => ['required', 'string', 'max:255'],
            'cta_title' => ['required', 'string', 'max:255'],
            'cta_description' => ['required', 'string', 'max:500'],
            'cta_primary_label' => ['required', 'string', 'max:120'],
            'nav_items' => ['required', 'array', 'min:1', 'max:8'],
            'nav_items.*.label' => ['required', 'string', 'max:120'],
            'nav_items.*.url' => ['required', 'string', 'max:255'],
            'destinations' => ['required', 'array', 'min:1', 'max:8'],
            'destinations.*.title' => ['required', 'string', 'max:120'],
            'destinations.*.subtitle' => ['required', 'string', 'max:255'],
            'destinations.*.image_url' => ['nullable', 'string', 'max:500'],
            'explore_filters' => ['required', 'array', 'min:1', 'max:8'],
            'explore_filters.*.title' => ['required', 'string', 'max:120'],
            'explore_filters.*.items' => ['required', 'array', 'min:1', 'max:8'],
            'explore_filters.*.items.*' => ['required', 'string', 'max:120'],
            'featured_hotels' => ['required', 'array', 'min:1', 'max:12'],
            'featured_hotels.*.brand' => ['required', 'string', 'max:60'],
            'featured_hotels.*.name' => ['required', 'string', 'max:120'],
            'featured_hotels.*.description' => ['required', 'string', 'max:500'],
            'featured_hotels.*.image_url' => ['nullable', 'string', 'max:500'],
            'featured_hotels.*.rating' => ['required', 'string', 'max:20'],
            'featured_hotels.*.location' => ['required', 'string', 'max:120'],
        ];
    }
}
