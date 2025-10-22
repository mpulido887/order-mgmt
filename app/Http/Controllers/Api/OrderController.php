<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\CreateOrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * POST /api/orders
     */
    public function store(OrderStoreRequest $request, CreateOrderService $service)
    {
        $user = $request->user();

        $order = $service->execute(
            clientId: $user->client_id,
            payload: $request->validated()
        );

        return (new OrderResource($order->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/orders/{id}
     */
    public function show(Request $request, int $id)
    {
        $clientId = $request->user()->client_id;

        $order = Order::query()
            ->forClient($clientId)
            ->with('items')
            ->findOrFail($id);

        return new OrderResource($order);
    }
}
