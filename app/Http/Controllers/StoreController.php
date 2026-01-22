<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    // List user's stores
    public function index(Request $request)
    {
        return $request->user()->stores;
    }

    // Public view of a store
    public function show($slug)
    {
        return Store::with(['products.images', 'user'])->where('slug', $slug)->firstOrFail();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
        ]);

        $slug = Str::slug($validated['name']);
        // Ensure unique slug
        $count = Store::where('slug', 'like', "$slug%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $storeData = [
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'status' => 'active',
        ];

        if ($request->hasFile('logo')) {
            $storeData['logo'] = $request->file('logo')->store('stores/logos', 'public');
        }

        if ($request->hasFile('banner')) {
            $storeData['banner'] = $request->file('banner')->store('stores/banners', 'public');
        }

        $store = $request->user()->stores()->create($storeData);

        return response()->json($store, 201);
    }

    public function update(Request $request, $id)
    {
        $store = $request->user()->stores()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
            'status' => 'in:active,inactive,suspended',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $store->name) {
            $slug = Str::slug($validated['name']);
            // Ensure unique slug
             $count = Store::where('slug', 'like', "$slug%")->where('id', '!=', $id)->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }
            $store->slug = $slug;
            $store->name = $validated['name'];
        }

        if (isset($validated['description'])) {
            $store->description = $validated['description'];
        }
        
        if (isset($validated['status'])) {
            $store->status = $validated['status'];
        }

        if ($request->hasFile('logo')) {
             if ($store->logo) Storage::disk('public')->delete($store->logo);
            $store->logo = $request->file('logo')->store('stores/logos', 'public');
        }

        if ($request->hasFile('banner')) {
             if ($store->banner) Storage::disk('public')->delete($store->banner);
            $store->banner = $request->file('banner')->store('stores/banners', 'public');
        }

        $store->save();

        return response()->json($store);
    }

    public function destroy(Request $request, $id)
    {
        $store = $request->user()->stores()->findOrFail($id);
        if ($store->logo) Storage::disk('public')->delete($store->logo);
        if ($store->banner) Storage::disk('public')->delete($store->banner);
        $store->delete();
        return response()->json(['message' => 'Store deleted']);
    }
}
