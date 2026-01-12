<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\MembershipPlan;
use App\Models\ReportedAd;
use App\Models\Contact;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'users_count' => User::count(),
            'products_count' => Product::count(),
            'categories_count' => Category::count(),
            'plans_count' => MembershipPlan::count(),
            'reports_count' => ReportedAd::count(),
            'messages_count' => Contact::count(),
        ]);
    }

    public function users()
    {
        return User::latest()->paginate(20);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function products()
    {
        return Product::with(['user', 'category', 'productType'])->latest()->paginate(20);
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
