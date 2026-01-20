<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Currency::query()->orderByDesc('id');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('currency', 'like', '%' . $search . '%')
                    ->orWhere('currency_code', 'like', '%' . $search . '%');
            });
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|max:10',
            'currency_code' => 'required|string|max:10',
        ]);

        $currency = Currency::create($validated);

        return response()->json($currency, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'currency' => 'sometimes|required|string|max:10',
            'currency_code' => 'sometimes|required|string|max:10',
        ]);

        $currency = Currency::findOrFail($id);
        $currency->update($validated);

        return response()->json($currency);
    }

    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);
        $currency->delete();

        return response()->json([
            'message' => 'Currency deleted successfully',
        ]);
    }
}

