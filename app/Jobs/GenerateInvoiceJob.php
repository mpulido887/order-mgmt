<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; // <- add
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array|int $backoff = [5, 30, 120];

    public int $orderId;
    public int $clientId;

    public function __construct(int $orderId, int $clientId)
    {
        $this->orderId  = $orderId;
        $this->clientId = $clientId;
    }

    public function handle(): void
    {
        $order = Order::query()
            ->where('id', $this->orderId)
            ->where('client_id', $this->clientId)
            ->with('items')
            ->first();

        if (! $order) {
            Log::warning('Invoice skipped: order not found or tenant mismatch', [
                'order_id'  => $this->orderId,
                'client_id' => $this->clientId,
                'attempts'  => $this->attempts(),
                'job_id'    => $this->job?->uuid(),
            ]);
            return;
        }

        $invoiceNumber = 'INV-' . $order->id;

        $payload = [
            'invoice_number' => $invoiceNumber,
            'order_id'       => $order->id,
            'client_id'      => $order->client_id,
            'total'          => (string) $order->total_amount,
            'lines'          => $order->items->map(fn ($it) => [
                'name'       => $it->name,
                'qty'        => $it->quantity,
                'unit_price' => (string) $it->unit_price,
                'line_total' => (string) $it->line_total,
            ])->values()->all(),
            'created_at'     => now()->toIso8601String(),
            'attempt'        => $this->attempts(),
        ];

        Invoice::updateOrCreate(
            ['order_id' => $order->id],
            [
                'invoice_number' => $invoiceNumber,
                'status'         => 'created',
                'payload'        => $payload,
            ]
        );

        Log::info('Invoice created & persisted', [
            'order_id'  => $order->id,
            'client_id' => $order->client_id,
            'attempts'  => $this->attempts(),
            'job_id'    => $this->job?->uuid(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Invoice::updateOrCreate(
            ['order_id' => $this->orderId],
            [
                'invoice_number' => 'INV-' . $this->orderId,
                'status'         => 'failed',
                'payload'        => [
                    'error'    => $e->getMessage(),
                    'trace'    => config('app.debug') ? $e->getTrace() : null,
                    'attempts' => $this->attempts(),
                ],
            ]
        );

        Log::error('Invoice job failed after retries', [
            'order_id'  => $this->orderId,
            'client_id' => $this->clientId,
            'attempts'  => $this->attempts(),
            'error'     => $e->getMessage(),
            'job_id'    => $this->job?->uuid(),
        ]);
    }
}
