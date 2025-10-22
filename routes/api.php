<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


