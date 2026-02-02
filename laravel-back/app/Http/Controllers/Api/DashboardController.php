<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Movement;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', 'month'); // 'day', 'week', 'month', 'year'

        // Dates
        $now = Carbon::now();
        $startDate = match($period) {
            'day' => $now->copy()->startOfDay(),
            'week' => $now->copy()->startOfWeek(),
            'month' => $now->copy()->startOfMonth(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };

        // Total products
        $totalProducts = Product::count();

        // Total categories
        $totalCategories = Category::count();

        // Total users
        $totalUsers = User::count();

        // Low stock products (stock < min_stock)
        $lowStockProducts = Product::whereColumn('stock', '<', 'min_stock')->count();

        // Out of stock products (stock = 0)
        $outOfStockProducts = Product::where('stock', 0)->count();

        // Total stock value
        $totalStockValue = Product::sum(DB::raw('stock * price'));

        // Movements statistics for the period
        $movementsInPeriod = Movement::where('date', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_movements,
                SUM(CASE WHEN type = "entree" THEN quantity ELSE 0 END) as total_entries,
                SUM(CASE WHEN type = "sortie" THEN quantity ELSE 0 END) as total_exits
            ')
            ->first();

        // Recent movements (last 10)
        $recentMovements = Movement::with(['product', 'user'])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // Top products by movements (most active)
        $topProductsByMovements = Product::withCount('movements')
            ->orderBy('movements_count', 'desc')
            ->limit(5)
            ->get();

        // Products by category
        $productsByCategory = Category::withCount('products')
            ->orderBy('products_count', 'desc')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'count' => $category->products_count,
                ];
            });

        // Stock movements over time (last 30 days)
        $movementsOverTime = Movement::selectRaw('DATE(date) as date, type, SUM(quantity) as total')
            ->where('date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date', 'type')
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('date')
            ->map(function ($dayMovements) {
                return [
                    'date' => $dayMovements->first()->date,
                    'entries' => $dayMovements->where('type', 'entree')->sum('total'),
                    'exits' => $dayMovements->where('type', 'sortie')->sum('total'),
                ];
            })
            ->values();

        // Recent inventories
        $recentInventories = Inventory::with(['user'])
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($inventory) {
                $totalDifference = $inventory->items()->sum('difference');
                return [
                    'id' => $inventory->id,
                    'date' => $inventory->date,
                    'status' => $inventory->status,
                    'user' => $inventory->user->name ?? 'N/A',
                    'total_difference' => $totalDifference,
                ];
            });

        return response()->json([
            'summary' => [
                'total_products' => $totalProducts,
                'total_categories' => $totalCategories,
                'total_users' => $totalUsers,
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'total_stock_value' => round($totalStockValue, 2),
            ],
            'movements' => [
                'period' => $period,
                'total' => $movementsInPeriod->total_movements ?? 0,
                'entries' => $movementsInPeriod->total_entries ?? 0,
                'exits' => $movementsInPeriod->total_exits ?? 0,
                'recent' => $recentMovements,
            ],
            'products' => [
                'top_by_movements' => $topProductsByMovements,
                'by_category' => $productsByCategory,
            ],
            'charts' => [
                'movements_over_time' => $movementsOverTime,
            ],
            'inventories' => [
                'recent' => $recentInventories,
            ],
        ]);
    }
}
