<?php

use App\Http\Controllers\BenchmarkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Performance Benchmark API Routes
|--------------------------------------------------------------------------
|
| T1.1 — Two endpoints for baseline load testing:
|   - Cheap:    GET /api/products       → simple paginated select, no joins
|   - Expensive: GET /api/orders/report → multi-table join + group by + order by
|
*/

// Cheap endpoint: simple paginated product listing
Route::get('/products', [BenchmarkController::class, 'products']);

// Expensive endpoint: order aggregation report by country
Route::get('/orders/report', [BenchmarkController::class, 'ordersReport']);
