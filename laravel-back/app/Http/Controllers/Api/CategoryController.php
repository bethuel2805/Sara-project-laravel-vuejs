<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Get all categories
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    /**
     * Create a new category
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:categories',
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Code et nom requis',
                'errors' => $validator->errors()
            ], 400);
        }

        $category = Category::create([
            'code' => $request->code,
            'name' => $request->name,
            'parent_id' => $request->parent_id ?? null,
            'description' => $request->description ?? null,
        ]);

        return response()->json($category, 201);
    }
}
