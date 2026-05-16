<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AnnualProcessedWasteController;
use App\Http\Controllers\QuoteController;

// Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'unauthenticated'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Public Routes (Landing Page)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);
Route::get('/waste/latest', [AnnualProcessedWasteController::class, 'latest']);
Route::post('/quotes', [QuoteController::class, 'store']);

// Protected Routes (CMS)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Product CMS
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::post('/products/{id}/images', [ProductController::class, 'addImages']);
    Route::delete('/products/{productId}/images/{imageId}', [ProductController::class, 'removeImage']);

    // Article CMS
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

    // Annual Processed Waste CMS
    Route::get('/waste', [AnnualProcessedWasteController::class, 'index']);
    Route::post('/waste', [AnnualProcessedWasteController::class, 'store']);
    Route::get('/waste/{id}', [AnnualProcessedWasteController::class, 'show']);
    Route::put('/waste/{id}', [AnnualProcessedWasteController::class, 'update']);
    Route::delete('/waste/{id}', [AnnualProcessedWasteController::class, 'destroy']);

    // Quotes CMS (gestión administrativa)
    Route::get('/quotes', [QuoteController::class, 'index']);
    Route::get('/quotes/{id}', [QuoteController::class, 'show']);
    Route::patch('/quotes/{id}/status', [QuoteController::class, 'updateStatus']);
    Route::delete('/quotes/{id}', [QuoteController::class, 'destroy']);
});

