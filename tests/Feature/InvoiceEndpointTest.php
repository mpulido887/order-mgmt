<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\GenerateInvoiceJob;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_when_invoice_absent_then_200_after_job(): void
    {
        $client = Client::factory()->create();
        $user   = User::factory()->create(['client_id' => $client->id]);

        $order = Order::factory()->create(['client_id' => $client->id]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/orders/{$order->id}/invoice")->assertStatus(404);

        (new GenerateInvoiceJob($order->id, $client->id))->handle();

        $this->getJson("/api/orders/{$order->id}/invoice")
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.status', 'created');
    }

    public function test_block_cross_tenant_invoice_access(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();

        $userB = User::factory()->create(['client_id' => $clientB->id]);

        $orderA = Order::factory()->create(['client_id' => $clientA->id]);
        OrderItem::factory()->create(['order_id' => $orderA->id]);

        (new GenerateInvoiceJob($orderA->id, $clientA->id))->handle();

        Sanctum::actingAs($userB);
        $this->getJson("/api/orders/{$orderA->id}/invoice")->assertStatus(404);
    }

    public function test_invoice_requires_auth(): void
    {
        $this->getJson('/api/orders/1/invoice')->assertStatus(401);
    }
}
