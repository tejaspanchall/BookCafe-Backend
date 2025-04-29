<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::all();
        return response()->json($products);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');
        
        $products = Product::where('name', 'like', "%{$query}%")
            ->orWhere('category', 'like', "%{$query}%")
            ->get();
            
        return response()->json($products);
    }
    
    public function getStock($id): JsonResponse
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }
} 