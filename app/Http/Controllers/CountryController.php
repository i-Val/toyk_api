<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $query = Country::query()->orderByDesc('id');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sortname' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'phonecode' => 'required|string|max:20',
        ]);

        $country = Country::create($validated);

        return response()->json($country, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sortname' => 'sometimes|required|string|max:50',
            'name' => 'sometimes|required|string|max:255',
            'phonecode' => 'sometimes|required|string|max:20',
        ]);

        $country = Country::findOrFail($id);
        $country->update($validated);

        return response()->json($country);
    }

    public function destroy($id)
    {
        $country = Country::findOrFail($id);
        $country->delete();

        return response()->json([
            'message' => 'Country deleted successfully',
        ]);
    }
}

