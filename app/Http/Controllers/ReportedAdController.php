<?php

namespace App\Http\Controllers;

use App\Models\ReportedAd;
use Illuminate\Http\Request;

class ReportedAdController extends Controller
{
    public function index()
    {
        return ReportedAd::with('product')->latest()->paginate(20);
    }

    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $product = \App\Models\Product::findOrFail($id);

        $report = ReportedAd::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Report submitted successfully', 'data' => $report], 201);
    }

    public function destroy($id)
    {
        $report = ReportedAd::findOrFail($id);
        $report->delete();
        return response()->json(['message' => 'Report deleted successfully']);
    }
}
