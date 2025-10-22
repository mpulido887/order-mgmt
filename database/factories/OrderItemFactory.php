<?php

namespace Database\Factories;
use App\Models\OrderItem;
use App\Models\Order;

use Illuminate\Database\Eloquent\Factories\Factory;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty  = fake()->numberBetween(1, 5);
        $unit = fake()->randomFloat(2, 5, 120);
        return [
            'order_id'   => Order::factory(),
            'name'       => fake()->word(),
            'quantity'   => $qty,
            'unit_price' => $unit,
            'line_total' => $qty * $unit,
        ];
    }
}
