<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\GenerateInvoiceJob;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateInvoiceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_idempotent_one_invoice_per_order(): void
    {
        $client = Client::factory()->create();
        $order  = Order::factory()->create(['client_id' => $client->id]);
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        (new GenerateInvoiceJob($order->id, $client->id))->handle();
        (new GenerateInvoiceJob($order->id, $client->id))->handle();

        $this->assertSame(1, Invoice::query()->where('order_id', $order->id)->count());
    }
}
