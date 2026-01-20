<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $query = City::query()
            ->with('state')
            ->orderByDesc('id');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($stateId = $request->query('state_id')) {
            $query->where('state_id', $stateId);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|integer|exists:states,id',
        ]);

        $city = City::create($validated);

        return response()->json($city, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'state_id' => 'sometimes|required|integer|exists:states,id',
        ]);

        $city = City::findOrFail($id);
        $city->update($validated);

        return response()->json($city);
    }

    public function destroy($id)
    {
        $city = City::findOrFail($id);
        $city->delete();

        return response()->json([
            'message' => 'City deleted successfully',
        ]);
    }
}

