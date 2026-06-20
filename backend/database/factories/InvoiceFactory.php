<?php

namespace Database\Factories;

use App\Domain\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500000, 3000000);
        $tax = $subtotal * 0.1;
        $grandTotal = $subtotal + $tax;

        return [
            'invoice_number' => strtoupper('INV-'.fake()->unique()->bothify('########')),
            'invoice_status' => 'draft',
            'issued_at' => now(),
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'service_amount' => 0,
            'discount_amount' => 0,
            'grand_total' => $grandTotal,
            'paid_amount' => 0,
            'remaining_amount' => $grandTotal,
        ];
    }
}
