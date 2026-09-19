<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('role:admin,manager');

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('role:admin,manager');

    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('role:admin,manager,cashier');
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    Route::get('/reports/sales', [ReportController::class, 'sales'])
        ->middleware('role:admin,manager');
});
