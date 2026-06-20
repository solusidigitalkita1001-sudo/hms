<?php

namespace Database\Factories;

use App\Domain\Billing\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 50000, 500000);
        $quantity = fake()->numberBetween(1, 5);
        $tax = $unitPrice * $quantity * 0.1;

        return [
            'item_type' => fake()->randomElement(['room_charge', 'amenity', 'food', 'service']),
            'item_name' => fake()->words(2, true),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'tax_amount' => $tax,
            'line_total' => ($unitPrice * $quantity) + $tax,
        ];
    }
}
