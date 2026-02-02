<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    /**
     * Get all inventories
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['user', 'items.product']);

        // Filtres
        if ($request->has('status') && $request->status !== 'all' && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Tri par date (plus récent en premier)
        $query->orderBy('date', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $inventories = $query->paginate($perPage);

        // Calculer l'écart global pour chaque inventaire
        $inventories->getCollection()->transform(function ($inventory) {
            $inventory->total_difference = $inventory->items->sum('difference');
            return $inventory;
        });

        return response()->json($inventories);
    }

    /**
     * Get a specific inventory with all items
     */
    public function show($id)
    {
        $inventory = Inventory::with(['user', 'items.product'])->find($id);

        if (!$inventory) {
            return response()->json([
                'message' => 'Inventaire non trouvé'
            ], 404);
        }

        $inventory->total_difference = $inventory->items->sum('difference');

        return response()->json($inventory);
    }

    /**
     * Create a new inventory (draft)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 400);
        }

        $inventory = Inventory::create([
            'date' => $request->date,
            'user_id' => auth()->id(),
            'status' => 'draft',
            'notes' => $request->notes ?? null,
        ]);

        $inventory->load(['user']);

        return response()->json($inventory, 201);
    }

    /**
     * Add a product to an inventory
     */
    public function addItem(Request $request, $inventoryId)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'actual_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 400);
        }

        $inventory = Inventory::find($inventoryId);
        if (!$inventory) {
            return response()->json([
                'message' => 'Inventaire non trouvé'
            ], 404);
        }

        if ($inventory->status !== 'draft') {
            return response()->json([
                'message' => 'Impossible d\'ajouter des produits à un inventaire terminé'
            ], 400);
        }

        // Vérifier si le produit n'est pas déjà dans l'inventaire
        $existingItem = InventoryItem::where('inventory_id', $inventoryId)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingItem) {
            return response()->json([
                'message' => 'Ce produit est déjà dans l\'inventaire'
            ], 400);
        }

        $product = Product::find($request->product_id);
        $expectedQuantity = $product->stock;
        $actualQuantity = $request->actual_quantity;
        $difference = $actualQuantity - $expectedQuantity;

        $item = InventoryItem::create([
            'inventory_id' => $inventoryId,
            'product_id' => $request->product_id,
            'expected_quantity' => $expectedQuantity,
            'actual_quantity' => $actualQuantity,
            'difference' => $difference,
            'notes' => $request->notes ?? null,
        ]);

        $item->load(['product']);

        return response()->json($item, 201);
    }

    /**
     * Update an inventory item
     */
    public function updateItem(Request $request, $inventoryId, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'actual_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 400);
        }

        $inventory = Inventory::find($inventoryId);
        if (!$inventory) {
            return response()->json([
                'message' => 'Inventaire non trouvé'
            ], 404);
        }

        if ($inventory->status !== 'draft') {
            return response()->json([
                'message' => 'Impossible de modifier un inventaire terminé'
            ], 400);
        }

        $item = InventoryItem::where('inventory_id', $inventoryId)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Élément d\'inventaire non trouvé'
            ], 404);
        }

        $actualQuantity = $request->actual_quantity;
        $difference = $actualQuantity - $item->expected_quantity;

        $item->update([
            'actual_quantity' => $actualQuantity,
            'difference' => $difference,
            'notes' => $request->notes ?? $item->notes,
        ]);

        $item->load(['product']);

        return response()->json($item);
    }

    /**
     * Remove an item from inventory
     */
    public function removeItem($inventoryId, $itemId)
    {
        $inventory = Inventory::find($inventoryId);
        if (!$inventory) {
            return response()->json([
                'message' => 'Inventaire non trouvé'
            ], 404);
        }

        if ($inventory->status !== 'draft') {
            return response()->json([
                'message' => 'Impossible de supprimer un élément d\'un inventaire terminé'
            ], 400);
        }

        $item = InventoryItem::where('inventory_id', $inventoryId)
            ->where('id', $itemId)
            ->first();

        if (!$item) {
            return response()->json([
                'message' => 'Élément d\'inventaire non trouvé'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'message' => 'Élément supprimé avec succès'
        ]);
    }

    /**
     * Complete an inventory (adjust stock automatically)
     */
    public function complete($id)
    {
        try {
            DB::beginTransaction();

            $inventory = Inventory::with('items.product')->find($id);

            if (!$inventory) {
                return response()->json([
                    'message' => 'Inventaire non trouvé'
                ], 404);
            }

            if ($inventory->status === 'completed') {
                return response()->json([
                    'message' => 'Cet inventaire est déjà terminé'
                ], 400);
            }

            // Ajuster le stock pour chaque produit
            foreach ($inventory->items as $item) {
                $product = $item->product;
                // Le stock devient la quantité constatée
                $product->stock = $item->actual_quantity;
                $product->save();

                // Créer un mouvement de correction automatique
                \App\Models\Movement::create([
                    'product_id' => $product->id,
                    'type' => $item->difference > 0 ? 'entree' : 'sortie',
                    'movement_type' => 'correction',
                    'quantity' => abs($item->difference),
                    'reason' => 'Ajustement inventaire #' . $inventory->id,
                    'user_id' => auth()->id(),
                    'date' => $inventory->date,
                ]);
            }

            // Marquer l'inventaire comme terminé
            $inventory->status = 'completed';
            $inventory->save();

            DB::commit();

            $inventory->load(['user', 'items.product']);
            $inventory->total_difference = $inventory->items->sum('difference');

            return response()->json($inventory);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la finalisation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an inventory
     */
    public function destroy($id)
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'message' => 'Inventaire non trouvé'
            ], 404);
        }

        if ($inventory->status === 'completed') {
            return response()->json([
                'message' => 'Impossible de supprimer un inventaire terminé'
            ], 400);
        }

        $inventory->delete();

        return response()->json([
            'message' => 'Inventaire supprimé avec succès'
        ]);
    }
}
