<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    const CACHE_DURATION = 3600;

    /**
     * Get all categories
     */
    public function index()
    {
        try {
            $categories = Cache::remember('categories:all', self::CACHE_DURATION, function() {
                return Category::withCount('books')->get();
            });
            
            return response()->json([
                'status' => 'success',
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            $categories = Category::withCount('books')->get();
            
            return response()->json([
                'status' => 'success',
                'categories' => $categories
            ]);
        }
    }

    /**
     * Add a new category
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:categories,name'
        ]);

        try {
            $category = Category::create([
                'name' => $request->name
            ]);

            $this->clearCategoryCache();

            return response()->json([
                'status' => 'success',
                'message' => 'Category added successfully',
                'category' => $category
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add category',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Clear category cache
     */
    private function clearCategoryCache()
    {
        Cache::forget('categories:all');
    }
} 