<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Client;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Orders\CreateOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_order_with_correct_totals_and_items(): void
    {
        $client = Client::factory()->create();
        $user   = User::factory()->create(['client_id' => $client->id]);

        $svc = app(CreateOrderService::class);

        $order = $svc->execute($user->client_id, [
            'items' => [
                ['name' => 'A', 'quantity' => 2, 'unit_price' => 10.50], // 21.00
                ['name' => 'B', 'quantity' => 3, 'unit_price' => 1.25],  // 3.75
            ],
        ]);

        $order->refresh();

        $this->assertSame(24.75, (float) $order->total_amount);
        $this->assertCount(2, $order->items);

        $count = OrderItem::query()->where('order_id', $order->id)->count();
        $this->assertSame(2, $count);
    }
}
