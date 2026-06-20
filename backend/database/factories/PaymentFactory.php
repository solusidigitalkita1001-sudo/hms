<?php

namespace Database\Factories;

use App\Domain\Billing\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_code' => strtoupper('PAY-'.fake()->unique()->bothify('########')),
            'payment_type' => fake()->randomElement(['full', 'partial', 'deposit']),
            'payment_status' => 'completed',
            'payment_method_code' => fake()->randomElement(['cash', 'transfer', 'card', 'qris']),
            'amount' => fake()->randomFloat(2, 100000, 5000000),
            'business_date' => now()->toDateString(),
            'paid_at' => now(),
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'refunded',
            'refunded_at' => now(),
        ]);
    }
}
