<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\MembershipPlan;
use App\Models\ReportedAd;
use App\Models\Contact;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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

    public function users(Request $request)
    {
        $query = User::query()->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate(20);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->is_admin) {
            return response()->json([
                'status' => false,
                'msg' => 'Cannot change status of admin user',
            ], 422);
        }

        $user->status = !$user->status;
        $user->save();

        return response()->json([
            'status' => true,
            'msg' => 'Status updated successfully',
            'data' => [
                'status' => (bool) $user->status,
            ],
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::with(['user', 'category', 'productType'])->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->paginate(20);
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function toggleProductStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->status = !$product->status;
        $product->save();

        return response()->json([
            'status' => true,
            'msg' => 'Status updated successfully',
            'data' => [
                'status' => (bool) $product->status,
            ],
        ]);
    }

    public function toggleProductFeatured($id)
    {
        $product = Product::findOrFail($id);
        $product->is_featured = !$product->is_featured;
        $product->save();

        return response()->json([
            'status' => true,
            'msg' => 'Featured flag updated successfully',
            'data' => [
                'is_featured' => (bool) $product->is_featured,
            ],
        ]);
    }

    public function transactions(Request $request)
    {
        $query = Payment::with('user')->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', '%' . $search . '%')
                    ->orWhere('currency', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('amount', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate(20);
    }

    public function notify(Request $request)
    {
        $data = $request->validate([
            'notification_type' => 'required|in:email,sms',
            'notification' => 'required|string',
        ]);

        $message = $data['notification'];

        if ($data['notification_type'] === 'email') {
            User::whereNotNull('email')
                ->where('email', '!=', '')
                ->chunk(50, function ($users) use ($message) {
                    $emails = $users->pluck('email')->all();

                    if (!empty($emails)) {
                        try {
                            Mail::raw($message, function ($mail) use ($emails) {
                                $mail->to($emails)->subject('Notification');
                            });
                        } catch (\Throwable $e) {
                            Log::error('Failed to send email notifications', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
        }

        if ($data['notification_type'] === 'sms') {
            $phones = User::whereNotNull('phone')
                ->where('phone', '!=', '')
                ->pluck('phone')
                ->all();

            if (!empty($phones)) {
                $apiToken = config('services.bulksmsnigeria.api_token');
                $from = config('services.bulksmsnigeria.from', 'Toyk Market');

                if ($apiToken) {
                    $payload = [
                        'body' => strip_tags($message),
                        'from' => $from,
                        'to' => $phones,
                        'api_token' => $apiToken,
                    ];

                    try {
                        Http::withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])->post('https://www.bulksmsnigeria.com/api/v2/sms', $payload);
                    } catch (\Throwable $e) {
                        Log::error('Failed to send SMS notifications', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Notification sent to all users successfully.',
        ]);
    }
}
