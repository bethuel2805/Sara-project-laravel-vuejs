<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Get all products
     */
    public function index()
    {
        $products = Product::with('category')->get();
        return response()->json($products);
    }

    /**
     * Create a new product
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'code' => 'required|string|unique:products',
            'category_id' => 'required|exists:categories,id',
            'supplier' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'optimal_stock' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Nom, code et catégorie requis',
                'errors' => $validator->errors()
            ], 400);
        }

        $category = Category::find($request->category_id);
        if (!$category) {
            return response()->json([
                'message' => 'Catégorie invalide'
            ], 400);
        }

        $product = Product::create([
            'name' => $request->name,
            'code' => $request->code,
            'category_id' => $request->category_id,
            'supplier' => $request->supplier ?? null,
            'price' => $request->price ?? 0,
            'stock' => $request->stock ?? 0,
            'min_stock' => $request->min_stock ?? 0,
            'optimal_stock' => $request->optimal_stock ?? 0,
        ]);

        $product->load('category');

        return response()->json($product, 201);
    }

    /**
     * Get a single product
     */
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        return response()->json($product);
    }

    /**
     * Update a product
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'code' => 'required|string|unique:products,code,' . $id,
            'category_id' => 'required|exists:categories,id',
            'supplier' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'optimal_stock' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 400);
        }

        $product->update([
            'name' => $request->name,
            'code' => $request->code,
            'category_id' => $request->category_id,
            'supplier' => $request->supplier ?? null,
            'price' => $request->price ?? 0,
            'stock' => $request->stock ?? 0,
            'min_stock' => $request->min_stock ?? 0,
            'optimal_stock' => $request->optimal_stock ?? 0,
        ]);

        $product->load('category');

        return response()->json($product);
    }

    /**
     * Delete a product
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Produit supprimé avec succès']);
    }
}
