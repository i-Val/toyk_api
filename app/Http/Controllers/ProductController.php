<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['user', 'category', 'productType', 'images']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('product_type_id')) {
            $query->where('product_type_id', $request->input('product_type_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = $request->input('lat');
            $lng = $request->input('lng');
            $radius = $request->input('radius', 500); // Default 500km

            // Haversine formula for distance in kilometers
            $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng) - radians($lng)) + sin(radians($lat)) * sin(radians(lat))))";
            
            $query->select('products.*')
                  ->selectRaw("{$haversine} as distance")
                  ->whereNotNull('lat')
                  ->whereNotNull('lng')
                  ->whereRaw("{$haversine} < ?", [$radius]);
                  
            if ($request->input('sort_by') === 'distance') {
                $query->orderBy('distance');
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'newest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'distance':
                // Already handled in location block if lat/lng present
                if (!$request->filled('lat') || !$request->filled('lng')) {
                     $query->latest(); // Fallback if no location
                }
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        if ($request->filled('limit')) {
            $products = $query->take($request->input('limit'))->get();
        } else {
            $products = $query->get();
        }

        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $wishlistIds = $user->wishlists()->pluck('product_id')->toArray();
            $products->transform(function ($product) use ($wishlistIds) {
                $product->is_wishlisted = in_array($product->id, $wishlistIds);
                return $product;
            });
        }

        return $products;
    }

    public function create()
    {
        return response()->json([
            'categories' => Category::all(),
            'types' => ProductType::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'product_type_id' => 'required|exists:product_types,id',
            'contact' => 'required|string',
            'expiry' => 'nullable|date',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product = $request->user()->products()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'category_id' => $validated['category_id'],
            'product_type_id' => $validated['product_type_id'],
            'contact' => $validated['contact'],
            'expiry' => $validated['expiry'] ?? null,
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'created_ip' => $request->ip()
        ]);

        if ($request->hasFile('images')) {
            $manager = new ImageManager(new Driver());
            
            foreach ($request->file('images') as $file) {
                // Generate unique filename
                $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Store original image
                $path = $file->storeAs('products', $filename, 'public');
                
                // Create resized versions
                // Ensure directories exist
                Storage::disk('public')->makeDirectory('products/120');
                Storage::disk('public')->makeDirectory('products/300');
                
                $image = $manager->read($file);
                
                // 120px width
                $image->scale(width: 120);
                $image->save(storage_path('app/public/products/120/' . $filename));
                
                // Reset image for next resize (read again or clone if needed, but read is safer)
                $image = $manager->read($file);
                
                // 300px width
                $image->scale(width: 300);
                $image->save(storage_path('app/public/products/300/' . $filename));
                
                // Save to DB (storing the relative path to original, same as before)
                $product->images()->create(['image' => $path]);
            }
        }

        return response()->json($product->load('images'), 201);
    }
    
    public function show($id)
    {
        $product = Product::with(['user', 'category', 'productType', 'images'])->findOrFail($id);
        $product->increment('total_views');

        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $product->is_wishlisted = $user->wishlists()->where('product_id', $id)->exists();
        }

        return $product;
    }

    public function myProducts(Request $request)
    {
        $query = Product::with(['category', 'productType', 'images'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('ad_type')) {
            $query->where('ad_type', $request->input('ad_type'));
        }

        $products = $query->latest()->get();
            
        return response()->json($products);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        if ($request->user()->id !== $product->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'category_id' => 'sometimes|exists:categories,id',
            'product_type_id' => 'sometimes|exists:product_types,id',
            'contact' => 'nullable|string',
            'expiry' => 'nullable|date',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $product->update($validated);

        if ($request->hasFile('images')) {
            $manager = new ImageManager(new Driver());
            
            foreach ($request->file('images') as $file) {
                // Generate unique filename
                $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Store original image
                $path = $file->storeAs('products', $filename, 'public');
                
                // Create resized versions
                // Ensure directories exist
                Storage::disk('public')->makeDirectory('products/120');
                Storage::disk('public')->makeDirectory('products/300');
                
                $image = $manager->read($file);
                
                // 120px width
                $image->scale(width: 120);
                $image->save(storage_path('app/public/products/120/' . $filename));
                
                // Reset image
                $image = $manager->read($file);
                
                // 300px width
                $image->scale(width: 300);
                $image->save(storage_path('app/public/products/300/' . $filename));
                
                // Save to DB
                $product->images()->create(['image' => $path]);
            }
        }

        return response()->json($product->load('images'));
    }

    public function deleteImage(Request $request, $id, $imageId)
    {
        $product = Product::findOrFail($id);

        if ($request->user()->id !== $product->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $image = $product->images()->findOrFail($imageId);
        
        // Delete files
        Storage::disk('public')->delete($image->image);
        $filename = basename($image->image);
        Storage::disk('public')->delete('products/120/' . $filename);
        Storage::disk('public')->delete('products/300/' . $filename);

        $image->delete();

        return response()->json(['message' => 'Image deleted']);
    }

    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        if ($request->user()->id !== $product->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete images from storage if needed (optional but good practice)
        // ...

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function upgrade(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        if ($request->user()->id !== $product->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->update([
            'ad_type' => 'upgraded',
            'is_featured' => true
        ]);

        return response()->json(['message' => 'Ad upgraded successfully', 'product' => $product]);
    }
}