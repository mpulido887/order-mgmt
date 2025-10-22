<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class ClientOrderController extends Controller
{
    /**
     * GET /api/clients/{id}/orders
     */
    public function index(Request $request, int $id)
    {
        $authClientId = $request->user()->client_id;

        // Anti-IDOR: the client_id must match the authenticated user
        if ($id !== $authClientId) {
            abort(404);
        }

        $orders = Order::query()
            ->forClient($authClientId)
            ->latest('created_at')
            ->with('items') // optional
            ->paginate(15);

        return OrderResource::collection($orders);
    }
}
