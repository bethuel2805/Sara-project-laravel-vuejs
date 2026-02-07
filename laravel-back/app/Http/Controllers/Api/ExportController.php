<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Movement;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export stock (products) as CSV (Excel compatible)
     */
    public function stockCsv(): StreamedResponse
    {
        $products = Product::with('category')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stock_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nom', 'Code', 'Catégorie', 'Fournisseur', 'Prix', 'Stock', 'Stock min', 'Stock optimal'], ';');
            foreach ($products as $p) {
                fputcsv($file, [
                    $p->name,
                    $p->code,
                    $p->category?->name ?? '',
                    $p->supplier ?? '',
                    $p->price,
                    $p->stock,
                    $p->min_stock,
                    $p->optimal_stock,
                ], ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export movements as CSV
     */
    public function movementsCsv(Request $request): StreamedResponse
    {
        $query = Movement::with(['product', 'user'])
            ->orderBy('date', 'desc');

        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to . ' 23:59:59');
        }

        $movements = $query->limit(5000)->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mouvements_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($movements) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Produit', 'Code', 'Type', 'Sous-type', 'Quantité', 'Motif', 'Utilisateur'], ';');
            foreach ($movements as $m) {
                fputcsv($file, [
                    $m->date->format('Y-m-d H:i'),
                    $m->product?->name ?? '',
                    $m->product?->code ?? '',
                    $m->type,
                    $m->movement_type,
                    $m->quantity,
                    $m->reason ?? '',
                    $m->user?->name ?? '',
                ], ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export inventories as CSV
     */
    public function inventoriesCsv(): StreamedResponse
    {
        $inventories = Inventory::with(['user', 'items.product'])
            ->orderBy('date', 'desc')
            ->limit(100)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventaires_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($inventories) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Statut', 'Créé par', 'Produit', 'Attendu', 'Constaté', 'Écart'], ';');
            foreach ($inventories as $inv) {
                if ($inv->items && $inv->items->count() > 0) {
                    foreach ($inv->items as $item) {
                        fputcsv($file, [
                            $inv->date->format('Y-m-d H:i'),
                            $inv->status,
                            $inv->user?->name ?? '',
                            $item->product?->name ?? '',
                            $item->expected_quantity,
                            $item->actual_quantity,
                            $item->difference,
                        ], ';');
                    }
                } else {
                    fputcsv($file, [
                        $inv->date->format('Y-m-d H:i'),
                        $inv->status,
                        $inv->user?->name ?? '',
                        '', '', '', '',
                    ], ';');
                }
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
