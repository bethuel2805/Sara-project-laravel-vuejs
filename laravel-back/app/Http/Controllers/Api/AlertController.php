<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Get all alerts (stock minimum, out of stock, overstock)
     */
    public function index(Request $request)
    {
        $alerts = [];

        // Rupture de stock (stock = 0)
        $outOfStock = Product::with('category')
            ->where('stock', 0)
            ->get();

        foreach ($outOfStock as $product) {
            $alerts[] = [
                'id' => 'rupture-' . $product->id,
                'type' => 'rupture',
                'product_id' => $product->id,
                'product' => $product->name . ' - ' . $product->code,
                'message' => 'Produit en rupture de stock.',
                'severity' => 'critical',
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'created_at' => $product->updated_at?->toIso8601String(),
            ];
        }

        // Seuil minimum atteint (stock > 0 mais <= min_stock)
        $lowStock = Product::with('category')
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'min_stock')
            ->get();

        foreach ($lowStock as $product) {
            $alerts[] = [
                'id' => 'seuil-' . $product->id,
                'type' => 'seuil',
                'product_id' => $product->id,
                'product' => $product->name . ' - ' . $product->code,
                'message' => 'Stock minimum atteint (' . $product->stock . ' unité(s) restante(s)).',
                'severity' => 'warning',
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'created_at' => $product->updated_at?->toIso8601String(),
            ];
        }

        // Surstock (stock > optimal_stock)
        $overstock = Product::with('category')
            ->whereColumn('stock', '>', 'optimal_stock')
            ->where('optimal_stock', '>', 0)
            ->get();

        foreach ($overstock as $product) {
            $alerts[] = [
                'id' => 'surstock-' . $product->id,
                'type' => 'surstock',
                'product_id' => $product->id,
                'product' => $product->name . ' - ' . $product->code,
                'message' => 'Niveau de stock supérieur à l\'optimal (' . $product->stock . ' > ' . $product->optimal_stock . ').',
                'severity' => 'info',
                'stock' => $product->stock,
                'optimal_stock' => $product->optimal_stock,
                'created_at' => $product->updated_at?->toIso8601String(),
            ];
        }

        // Filtrer par type si demandé
        $type = $request->get('type');
        if ($type && $type !== 'all') {
            $alerts = array_filter($alerts, fn($a) => $a['type'] === $type);
        }

        // Trier par sévérité (critical > warning > info)
        usort($alerts, function ($a, $b) {
            $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
            return ($order[$a['severity']] ?? 3) - ($order[$b['severity']] ?? 3);
        });

        return response()->json(['data' => array_values($alerts)]);
    }
}
