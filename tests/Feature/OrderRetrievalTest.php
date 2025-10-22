<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderRetrievalTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_order_for_same_tenant_and_404_cross_tenant(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();

        $userA = User::factory()->create(['client_id' => $clientA->id]);
        $userB = User::factory()->create(['client_id' => $clientB->id]);

        $orderA = Order::factory()->create(['client_id' => $clientA->id]);
        OrderItem::factory()->count(2)->create(['order_id' => $orderA->id]);

        Sanctum::actingAs($userA);
        $this->getJson("/api/orders/{$orderA->id}")
            ->assertOk()
            ->assertJsonPath('data.client_id', $clientA->id);

        Sanctum::actingAs($userB);
        $this->getJson("/api/orders/{$orderA->id}")
            ->assertStatus(404);
    }

    public function test_list_orders_only_for_same_client_and_block_cross_tenant(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();

        $userA = User::factory()->create(['client_id' => $clientA->id]);

        Order::factory()->count(3)->create(['client_id' => $clientA->id]);
        Order::factory()->count(2)->create(['client_id' => $clientB->id]);

        Sanctum::actingAs($userA);

        $this->getJson("/api/clients/{$clientA->id}/orders")
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->getJson("/api/clients/{$clientB->id}/orders")
            ->assertStatus(404);
    }

    public function test_get_requires_auth(): void
    {
        $this->getJson('/api/orders/1')->assertStatus(401);
        $this->getJson('/api/clients/1/orders')->assertStatus(401);
    }
}
