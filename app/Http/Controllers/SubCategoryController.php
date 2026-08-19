<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ProviderService;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $category = Category::query()
            ->where('id', $request->category_id)
            ->where('status', true)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $subcategories = ProviderService::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->whereHas('provider', function ($query) {
                $query->where('status', true);
            })
            ->whereNotNull('sub_category')
            ->where('sub_category', '!=', '')
            ->selectRaw('sub_category as name, COUNT(*) as services_count')
            ->groupBy('sub_category')
            ->orderBy('sub_category')
            ->get();

        return response()->json([
            'success' => true,

            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
            ],

            'subcategories' => $subcategories,
        ]);
    }
}