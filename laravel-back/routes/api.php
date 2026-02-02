<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\ForceJsonResponse;

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Public routes
Route::prefix('v1/auth')->group(function () {
    Route::get('/can-register', [AuthController::class, 'canRegister']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api');
});

// Protected routes (require authentication)
Route::middleware('auth:api')->prefix('v1')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
    });

    // Movements
    Route::prefix('movements')->group(function () {
        Route::get('/', [MovementController::class, 'index']);
        Route::post('/', [MovementController::class, 'store']);
        Route::get('/summary', [MovementController::class, 'summary']);
        Route::get('/product/{productId}', [MovementController::class, 'byProduct']);
        Route::get('/{id}', [MovementController::class, 'show']);
        Route::delete('/{id}', [MovementController::class, 'destroy']);
    });

    // Inventories
    Route::prefix('inventories')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::post('/', [InventoryController::class, 'store']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::post('/{id}/complete', [InventoryController::class, 'complete']);
        Route::delete('/{id}', [InventoryController::class, 'destroy']);
        Route::post('/{inventoryId}/items', [InventoryController::class, 'addItem']);
        Route::put('/{inventoryId}/items/{itemId}', [InventoryController::class, 'updateItem']);
        Route::delete('/{inventoryId}/items/{itemId}', [InventoryController::class, 'removeItem']);
    });

    // Users (admin only)
    Route::prefix('users')->middleware(AdminOnly::class)->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
});
