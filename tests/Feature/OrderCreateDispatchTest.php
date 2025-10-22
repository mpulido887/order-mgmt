<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\GenerateInvoiceJob;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCreateDispatchTest extends TestCase
{
    use DatabaseMigrations; 

    public function test_it_creates_order_and_dispatches_invoice_job_after_commit(): void
    {
        $client = Client::factory()->create();
        $user   = User::factory()->create(['client_id' => $client->id]);

        Sanctum::actingAs($user);
        Queue::fake();

        $payload = [
            'items' => [
                ['name' => 'A', 'quantity' => 2, 'unit_price' => 10.50],
                ['name' => 'B', 'quantity' => 1, 'unit_price' => 99.99],
            ],
        ];

        $res = $this->postJson('/api/orders', $payload);
        $res->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id','client_id','status','total_amount',
                    'items' => [['id','name','quantity','unit_price','line_total']]
                ],
            ]);

        $orderId = $res->json('data.id');

        Queue::assertPushed(GenerateInvoiceJob::class, function (GenerateInvoiceJob $job) use ($orderId, $client) {
            return $job->orderId === $orderId && $job->clientId === $client->id;
        });
    }

    public function test_it_validates_bad_payloads_with_422(): void
    {
        $client = Client::factory()->create();
        $user   = User::factory()->create(['client_id' => $client->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/orders', ['items' => []])->assertStatus(422);

        $this->postJson('/api/orders', [
            'items' => [['name' => 'X', 'quantity' => 0, 'unit_price' => 10]],
        ])->assertStatus(422);
    }

    public function test_post_requires_auth(): void
    {
        $this->postJson('/api/orders', [
            'items' => [['name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertStatus(401);
    }
}
