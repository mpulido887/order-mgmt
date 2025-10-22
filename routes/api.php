<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientOrderController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InvoiceController;

Route::middleware('auth:sanctum')->get('/ping', function (Request $request) {
    return response()->json([
        'ok' => true,
        'user' => [
            'id'        => $request->user()->id,
            'client_id' => $request->user()->client_id,
            'email'     => $request->user()->email,
        ],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    
    // Returns the order
    Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');

    // Returns the orders for the client
    Route::get('/clients/{id}/orders', [ClientOrderController::class, 'index'])
        ->whereNumber('id');

    // Returns the invoice for the order
    Route::get('/orders/{id}/invoice', [InvoiceController::class, 'show'])->whereNumber('id');

    // Returns the status of the invoice
    Route::middleware('auth:sanctum')->get('/orders/{id}/invoice/status', [InvoiceController::class, 'status']);

});


