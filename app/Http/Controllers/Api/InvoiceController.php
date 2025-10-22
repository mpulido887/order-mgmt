<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * GET /api/orders/{id}/invoice
     * Returns the invoice for the order if it exists
     */
    public function show(Request $request, int $id)
    {
        $clientId = $request->user()->client_id;

        $order = Order::query()
            ->forClient($clientId)
            ->findOrFail($id);

        $invoice = Invoice::query()
            ->where('order_id', $order->id)
            ->first();

        if (! $invoice) {
            abort(404);
        }

        return new InvoiceResource($invoice);
    }

    public function status(Request $request, int $id)
    {
        $clientId = $request->user()->client_id;
        $order = \App\Models\Order::query()->forClient($clientId)->findOrFail($id);
        $invoice = \App\Models\Invoice::query()->where('order_id', $order->id)->first();

        return response()->json([
            'order_id' => $order->id,
            'invoice'  => $invoice ? [
                'id'     => $invoice->id,
                'status' => $invoice->status,
            ] : null,
            'state'   => $invoice ? 'ready' : 'pending',
        ], $invoice ? 200 : 202);
    }

}
