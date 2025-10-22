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

        // Anti-IDOR: the client_id must match the authenticated user
        $order = \App\Models\Order::query()->forClient($clientId)->findOrFail($id);

        $invoice = \App\Models\Invoice::query()->where('order_id', $order->id)->first();

        if (! $invoice) {
            // The invoice has not been generated yet but the order exists
            return response()->json([
                'status'  => 'pending',
                'message' => 'Invoice not generated yet. Please retry shortly.',
                'order_id' => $order->id,
            ], 202);
        }

        return new \App\Http\Resources\InvoiceResource($invoice);
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
