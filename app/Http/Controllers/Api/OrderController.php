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

    public function listByClient(Request $request, int $id)
    {
        // Enforce same-tenant
        $authClientId = $request->user()->client_id;
        abort_if($id !== $authClientId, 404);

        // per_page: default 15, min 1, max 100
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $orders = Order::query()
            ->forClient($authClientId)
            ->with('items')
            ->orderByDesc('id')
            ->paginate($perPage);

        // Resource collection works with paginator (adds meta/links)
        return OrderResource::collection($orders);
    }
}
