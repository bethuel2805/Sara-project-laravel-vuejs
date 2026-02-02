<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MovementController extends Controller
{
    /**
     * Get all movements with filters
     */
    public function index(Request $request)
    {
        $query = Movement::with(['product', 'user']);

        // Filtres
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Tri par date (plus récent en premier)
        $query->orderBy('date', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $movements = $query->paginate($perPage);

        return response()->json($movements);
    }

    /**
     * Get movements for a specific product
     */
    public function byProduct($productId)
    {
        $movements = Movement::with(['product', 'user'])
            ->byProduct($productId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($movements);
    }

    /**
     * Get a specific movement
     */
    public function show($id)
    {
        $movement = Movement::with(['product', 'user'])->find($id);

        if (!$movement) {
            return response()->json([
                'message' => 'Mouvement non trouvé'
            ], 404);
        }

        return response()->json($movement);
    }

    /**
     * Create a new movement
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:entree,sortie',
            'movement_type' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 400);
        }

        // Valider le movement_type selon le type
        $validEntryTypes = ['achat', 'retour', 'correction'];
        $validExitTypes = ['vente', 'perte', 'casse', 'expiration'];

        if ($request->type === 'entree' && !in_array($request->movement_type, $validEntryTypes)) {
            return response()->json([
                'message' => 'Type de mouvement invalide pour une entrée. Types valides: ' . implode(', ', $validEntryTypes)
            ], 400);
        }

        if ($request->type === 'sortie' && !in_array($request->movement_type, $validExitTypes)) {
            return response()->json([
                'message' => 'Type de mouvement invalide pour une sortie. Types valides: ' . implode(', ', $validExitTypes)
            ], 400);
        }

        // Utiliser une transaction pour garantir la cohérence
        try {
            DB::beginTransaction();

            // Récupérer le produit
            $product = Product::findOrFail($request->product_id);
            $oldStock = $product->stock;

            // Vérifier le stock pour les sorties
            if ($request->type === 'sortie') {
                if ($product->stock < $request->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Stock insuffisant. Stock actuel: ' . $product->stock . ', Quantité demandée: ' . $request->quantity
                    ], 400);
                }
            }

            // Créer le mouvement
            $movement = Movement::create([
                'product_id' => $request->product_id,
                'type' => $request->type,
                'movement_type' => $request->movement_type,
                'quantity' => $request->quantity,
                'reason' => $request->reason,
                'user_id' => auth()->id(),
                'date' => $request->date,
            ]);

            // Mettre à jour le stock du produit
            if ($request->type === 'entree') {
                $product->stock += $request->quantity;
            } else {
                $product->stock -= $request->quantity;
            }

            $product->save();

            DB::commit();

            // Charger les relations pour la réponse
            $movement->load(['product', 'user']);

            return response()->json($movement, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création du mouvement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a movement (annule l'effet sur le stock)
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $movement = Movement::with('product')->find($id);

            if (!$movement) {
                return response()->json([
                    'message' => 'Mouvement non trouvé'
                ], 404);
            }

            $product = $movement->product;

            // Annuler l'effet sur le stock
            if ($movement->type === 'entree') {
                $product->stock -= $movement->quantity;
                // Vérifier que le stock ne devient pas négatif
                if ($product->stock < 0) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Impossible de supprimer ce mouvement. Le stock deviendrait négatif.'
                    ], 400);
                }
            } else {
                $product->stock += $movement->quantity;
            }

            $product->save();

            // Supprimer le mouvement
            $movement->delete();

            DB::commit();

            return response()->json([
                'message' => 'Mouvement supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics
     */
    public function summary(Request $request)
    {
        $query = Movement::query();

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        $totalEntries = (clone $query)->entree()->sum('quantity');
        $totalExits = (clone $query)->sortie()->sum('quantity');
        $totalMovements = $query->count();

        return response()->json([
            'total_entries' => $totalEntries,
            'total_exits' => $totalExits,
            'total_movements' => $totalMovements,
            'net_movement' => $totalEntries - $totalExits,
        ]);
    }
}
