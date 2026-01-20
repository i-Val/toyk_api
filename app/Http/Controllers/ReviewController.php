<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function adminIndex(Request $request)
    {
        $query = Review::with(['user', 'product'])->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhere('rating', $search);
            });
        }

        return $query->paginate(20);
    }

    public function adminShow($id)
    {
        $review = Review::with(['user', 'product'])->findOrFail($id);

        return response()->json($review);
    }

    public function toggleStatus($id)
    {
        $review = Review::findOrFail($id);

        $review->status = !$review->status;
        $review->save();

        return response()->json([
            'status' => true,
            'msg' => 'Status updated successfully',
            'data' => [
                'status' => (bool) $review->status,
            ],
        ]);
    }

    public function index($id)
    {
        return Review::with('user')
            ->where('product_id', $id)
            ->latest()
            ->get();
    }

    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);

        $product = Product::findOrFail($id);

        $review = Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return response()->json($review->load('user'), 201);
    }
}
