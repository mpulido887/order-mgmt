<?php

namespace App\Services\Orders;

use App\Jobs\GenerateInvoiceJob;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class CreateOrderService
{
    /**
     * Runs the order creation process and returns the created order
     *
     * @param  int   $clientId    Client owner (
     * @param  array $payload     ['items' => [ ['name'=>..., 'quantity'=>..., 'unit_price'=>...], ... ]]
     * @return \App\Models\Order  
     */
    public function execute(int $clientId, array $payload): Order
    {
        return DB::transaction(function () use ($clientId, $payload) {
            // 1) Create order record 
            /** @var Order $order */
            $order = Order::create([
                'client_id'    => $clientId,
                'status'       => 'created',
                'total_amount' => 0,
            ]);

            // 2) Persist order items
            $total = 0.0;

            foreach ($payload['items'] as $raw) {
                $qty  = (int) $raw['quantity'];
                $price = (float) $raw['unit_price'];
                $line = $qty * $price;
                // Round to 2 decimal places
                $line = round($line, 2);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'name'       => (string) $raw['name'],
                    'quantity'   => $qty,
                    'unit_price' => round($price, 2),
                    'line_total' => $line,
                ]);

                $total += $line;
            }

            $order->update(['total_amount' => round($total, 2)]);

            // 3) Load order items
            $order->load('items');

            // 4) Queue invoice generation
            GenerateInvoiceJob::dispatch(
                orderId: $order->id,
                clientId: $clientId
            )->onQueue('invoices')->afterCommit();

            return $order;
        });
    }
}
