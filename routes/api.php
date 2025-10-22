<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InvoiceController;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    Route::get('/ping', function (Request $request) {
        return response()->json([
            'ok' => true,
            'user' => [
                'id'        => $request->user()->id,
                'client_id' => $request->user()->client_id,
                'email'     => $request->user()->email,
            ],
        ]);
    });

    // Orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
    Route::get('/clients/{id}/orders', [OrderController::class, 'listByClient'])->whereNumber('id');

    // Invoices
    Route::get('/orders/{id}/invoice', [InvoiceController::class, 'show'])->whereNumber('id');
});
