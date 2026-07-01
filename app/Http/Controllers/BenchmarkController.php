<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BenchmarkController extends Controller
{
    /**
     * T1.1 — CHEAP endpoint.
     *
     * Simple paginated product listing — no joins, no aggregations.
     * Represents a typical cheap GET request that should be fast even at scale.
     */
    public function products(): JsonResponse
    {
        $products = DB::table('products')
            ->select('id', 'name', 'sku', 'price', 'stock_quantity')
            ->orderBy('id')
            ->paginate(25);

        return response()->json($products);
    }

    /**
     * T1.1 — EXPENSIVE endpoint.
     *
     * Aggregates order stats per customer country:
     *   - total revenue
     *   - number of orders
     *   - total items sold
     *
     * Why it hurts:
     *   - Joins customers (20k), orders (10k), order_items (250k), products (5k)
     *   - GROUP BY unindexed 'country' column
     *   - ORDER BY computed aggregate on large dataset
     */
    public function ordersReport(): JsonResponse
    {
        $report = DB::table('customers as c')
            ->select(
                'c.country',
                DB::raw('COUNT(DISTINCT o.id) AS order_count'),
                DB::raw('SUM(oi.quantity) AS total_items_sold'),
                DB::raw('ROUND(SUM(oi.subtotal), 2) AS total_revenue'),
                DB::raw('ROUND(AVG(o.total_amount), 2) AS avg_order_value')
            )
            ->join('orders as o', 'o.customer_id', '=', 'c.id')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->groupBy('c.country')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json([
            'total_countries' => $report->count(),
            'data' => $report,
        ]);
    }
}
