<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $query = State::query()
            ->with('country')
            ->orderByDesc('id');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($countryId = $request->query('country_id')) {
            $query->where('country_id', $countryId);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|integer|exists:countries,id',
        ]);

        $state = State::create($validated);

        return response()->json($state, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'country_id' => 'sometimes|required|integer|exists:countries,id',
        ]);

        $state = State::findOrFail($id);
        $state->update($validated);

        return response()->json($state);
    }

    public function destroy($id)
    {
        $state = State::findOrFail($id);
        $state->delete();

        return response()->json([
            'message' => 'State deleted successfully',
        ]);
    }
}

