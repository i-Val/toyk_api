<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::whereNull('parent_id')->with('children')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'fa_icon' => 'nullable|string',
        ]);

        $category = Category::create($validated);
        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'fa_icon' => 'nullable|string',
        ]);

        $category = Category::findOrFail($id);
        $category->update($validated);
        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }

    public function allForAdmin()
    {
        $categories = Category::orderBy('title')->get(['id', 'title']);

        return response()->json([
            'status' => true,
            'data' => $categories->map(function (Category $category) {
                return [
                    'id' => $category->id,
                    'name' => $category->title,
                ];
            }),
        ]);
    }
}
