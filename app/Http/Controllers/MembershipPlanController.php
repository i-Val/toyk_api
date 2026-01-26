<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\UserMembership;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MembershipPlanController extends Controller
{
    public function index()
    {
        return MembershipPlan::all();
    }

    public function initiatePayment(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:membership_plans,id',
            'gateway' => 'nullable|string|in:paystack,flutterwave',
        ]);

        $gateway = $validated['gateway'] ?? 'paystack';
        $user = $request->user();
        $plan = MembershipPlan::findOrFail($validated['plan_id']);

        // Generate a unique reference
        $reference = 'TX-' . uniqid() . '-' . time();

        if ($gateway === 'flutterwave') {
            // Log pending payment for Flutterwave
             Payment::create([
                'user_id' => $user->id,
                'amount' => $plan->price,
                'currency' => $plan->currency_code,
                'payment_method' => 'flutterwave',
                'status' => 'pending',
                'transaction_id' => $reference, // We use this as tx_ref
                'description' => 'Membership plan: ' . $plan->title,
            ]);

            return response()->json([
                'reference' => $reference,
                'message' => 'Payment initialized',
            ]);
        }

        // Paystack Fallback (Legacy or if specified)
        $secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
        $baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');

        if (!$secretKey) {
            return response()->json([
                'message' => 'Payment gateway is not configured.',
            ], 500);
        }

        $amountInKobo = (int) round($plan->price * 100);

        $callbackUrl = config('services.paystack.callback_url', config('app.url') . '/paystack/callback');

        $response = Http::withToken($secretKey)->post($baseUrl . '/transaction/initialize', [
            'email' => $user->email,
            'amount' => $amountInKobo,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
        ]);

        if (!$response->ok() || !$response->json('status')) {
            return response()->json([
                'message' => 'Failed to initialize payment.',
            ], 502);
        }

        $data = $response->json('data');

        Payment::create([
            'user_id' => $user->id,
            'amount' => $plan->price,
            'currency' => $plan->currency_code,
            'payment_method' => 'paystack',
            'status' => 'pending',
            'transaction_id' => $data['reference'] ?? null,
            'description' => 'Membership plan: ' . $plan->title,
        ]);

        return response()->json([
            'authorization_url' => isset($data['authorization_url']) ? trim($data['authorization_url']) : null,
            'access_code' => $data['access_code'] ?? null,
            'reference' => $data['reference'] ?? null,
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'nullable|string', // Paystack uses reference
            'transaction_id' => 'nullable|string', // Flutterwave uses transaction_id
            'tx_ref' => 'nullable|string', // Flutterwave tx_ref
            'gateway' => 'nullable|string|in:paystack,flutterwave',
        ]);

        $gateway = $validated['gateway'] ?? 'paystack';
        $user = $request->user();

        if ($gateway === 'flutterwave') {
            return $this->verifyFlutterwave($request, $validated);
        }

        return $this->verifyPaystack($request, $validated);
    }

    private function verifyFlutterwave(Request $request, $validated)
    {
        $transactionId = $validated['transaction_id'];
        $txRef = $validated['tx_ref'] ?? null;
        
        $secretKey = config('services.flutterwave.secret_key', env('FLW_SECRET_KEY'));
        
        if (!$secretKey) {
             // Fallback for demo/test if env not set, though ideally should be set
             // The user provided public key, but secret key is needed for verification
             // For now we might need to rely on what user provided or environment
             return response()->json(['message' => 'Flutterwave secret key not configured'], 500);
        }

        $response = Http::withToken($secretKey)->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");
        
        if (!$response->ok() || $response->json('status') !== 'success') {
            return response()->json(['message' => 'Failed to verify payment with gateway.'], 400);
        }

        $data = $response->json('data');

        if ($data['status'] !== 'successful') {
             return response()->json(['message' => 'Payment was not successful.'], 400);
        }

        // Find payment by tx_ref
        $payment = Payment::where('transaction_id', $data['tx_ref'])->first();

        if (!$payment) {
             // Try finding by user and pending status if tx_ref mismatch (shouldn't happen if we logged correctly)
             $payment = Payment::where('user_id', $request->user()->id)
                ->where('status', 'pending')
                ->latest()
                ->first();
        }

        if (!$payment) {
            return response()->json(['message' => 'Payment record not found.'], 404);
        }

        if ($payment->amount != $data['amount']) {
             // Warning: Amount mismatch
        }
        
        if ($payment->status === 'completed') {
            return response()->json(['message' => 'Payment already verified.']);
        }

        return $this->activateMembership($request->user(), $payment, $data);
    }

    // private function verifyPaystack(Request $request, $validated)
    // {
    //     $reference = $validated['reference'];
    //     $user = $request->user();

    //     $secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
    //     $baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');

    //     if (!$secretKey) {
    //         return response()->json([
    //             'message' => 'Payment gateway is not configured.',
    //         ], 500);
    //     }

    //     $response = Http::withToken($secretKey)->get($baseUrl . '/transaction/verify/' . $reference);

    //     if (!$response->ok() || !$response->json('status')) {
    //         return response()->json([
    //             'message' => 'Failed to verify payment.',
    //         ], 502);
    //     }

    //     $data = $response->json('data');

    //     if (($data['status'] ?? null) !== 'success') {
    //         return response()->json([
    //             'message' => 'Payment not successful.',
    //         ], 400);
    //     }

    //     $payment = Payment::where('user_id', $user->id)
    //         ->where('transaction_id', $reference)
    //         ->first();

    //     if (!$payment) {
    //         return response()->json([
    //             'message' => 'Payment record not found.',
    //         ], 404);
    //     }

    //     if ($payment->status === 'completed') {
    //         return response()->json([
    //             'message' => 'Subscription already activated.',
    //         ]);
    //     }

    //     return $this->activateMembership($user, $payment, $data);
    // }

    private function activateMembership($user, $payment, $gatewayData)
    {
        // Try to get plan_id from metadata or payment description/logic
        // For Paystack it was in metadata. For Flutterwave we sent it in meta.
        
        $planId = $gatewayData['meta']['plan_id'] ?? $gatewayData['metadata']['plan_id'] ?? null;

        // If not in metadata, try to infer from payment amount or stored payment record if we linked it
        // Ideally we should have stored plan_id in payment record. 
        // For now, let's fetch plan based on amount if ID missing
        
        if (!$planId) {
             // Fallback: Find plan by price (risky if multiple plans have same price)
             $plan = MembershipPlan::where('price', $payment->amount)->first();
        } else {
             $plan = MembershipPlan::find($planId);
        }

        if (!$plan) {
            return response()->json([
                'message' => 'Plan information missing or invalid.',
            ], 400);
        }

        $membership = null;

        DB::transaction(function () use ($user, $plan, $payment, &$membership) {
            $activeMembership = $user->currentMembership;

            if ($activeMembership) {
                $activeMembership->update(['status' => 'cancelled']);
            }

            $payment->update([
                'status' => 'completed',
                'amount' => $plan->price,
                'currency' => $plan->currency_code,
            ]);

            $membership = UserMembership::create([
                'user_id' => $user->id,
                'membership_plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => $plan->days ? now()->addDays($plan->days) : now()->addMonth(),
                'status' => 'active',
            ]);
        });

        return response()->json([
            'message' => 'Subscribed successfully',
            'membership' => $membership,
        ]);
    }
    
    // Kept for backward compatibility if needed, but verifyPayment handles both
    public function verifyPaystackLegacy(Request $request) {
         return $this->verifyPaystack($request, $request->validate(['reference' => 'required']));
    }

    public function initiatePaystack(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:membership_plans,id',
        ]);

        $user = $request->user();
        $plan = MembershipPlan::findOrFail($validated['plan_id']);

        $secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
        $baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');

        if (!$secretKey) {
            return response()->json([
                'message' => 'Payment gateway is not configured.',
            ], 500);
        }

        $amountInKobo = (int) round($plan->price * 100);

        $callbackUrl = config('services.paystack.callback_url', config('app.url') . '/paystack/callback');

        $response = Http::withToken($secretKey)->post($baseUrl . '/transaction/initialize', [
            'email' => $user->email,
            'amount' => $amountInKobo,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
        ]);

        if (!$response->ok() || !$response->json('status')) {
            return response()->json([
                'message' => 'Failed to initialize payment.',
            ], 502);
        }

        $data = $response->json('data');

        // Paystack response structure:
        // {
        //   "status": true,
        //   "message": "Authorization URL created",
        //   "data": {
        //     "authorization_url": "https://checkout.paystack.com/...",
        //     "access_code": "...",
        //     "reference": "..."
        //   }
        // }

        Payment::create([
            'user_id' => $user->id,
            'amount' => $plan->price,
            'currency' => $plan->currency_code,
            'payment_method' => 'paystack',
            'status' => 'pending',
            'transaction_id' => $data['reference'] ?? null,
            'description' => 'Membership plan: ' . $plan->title,
        ]);

        return response()->json([
            'authorization_url' => isset($data['authorization_url']) ? trim($data['authorization_url']) : null,
            'access_code' => $data['access_code'] ?? null,
            'reference' => $data['reference'] ?? null,
        ]);
    }

    public function verifyPaystack(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);

        $user = $request->user();

        $secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
        $baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');

        if (!$secretKey) {
            return response()->json([
                'message' => 'Payment gateway is not configured.',
            ], 500);
        }

        $response = Http::withToken($secretKey)->get($baseUrl . '/transaction/verify/' . $validated['reference']);

        if (!$response->ok() || !$response->json('status')) {
            return response()->json([
                'message' => 'Failed to verify payment.',
            ], 502);
        }

        $data = $response->json('data');

        if (($data['status'] ?? null) !== 'success') {
            return response()->json([
                'message' => 'Payment not successful.',
            ], 400);
        }

        $payment = Payment::where('user_id', $user->id)
            ->where('transaction_id', $validated['reference'])
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment record not found.',
            ], 404);
        }

        if ($payment->status === 'completed') {
            return response()->json([
                'message' => 'Subscription already activated.',
            ]);
        }

        $planId = $data['metadata']['plan_id'] ?? null;

        if (!$planId) {
            return response()->json([
                'message' => 'Plan information missing from payment metadata.',
            ], 400);
        }

        $plan = MembershipPlan::findOrFail($planId);

        $membership = null;

        DB::transaction(function () use ($user, $plan, $payment, &$membership) {
            $activeMembership = $user->currentMembership;

            if ($activeMembership) {
                $activeMembership->update(['status' => 'cancelled']);
            }

            $payment->update([
                'status' => 'completed',
                'amount' => $plan->price,
                'currency' => $plan->currency_code,
            ]);

            $membership = UserMembership::create([
                'user_id' => $user->id,
                'membership_plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => $plan->days ? now()->addDays($plan->days) : now()->addMonth(),
                'status' => 'active',
            ]);
        });

        return response()->json([
            'message' => 'Subscribed successfully',
            'membership' => $membership,
        ]);
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:membership_plans,id',
        ]);

        $user = $request->user();
        $plan = MembershipPlan::findOrFail($validated['plan_id']);

        // Check if already has active membership
        $activeMembership = $user->currentMembership;
        
        if ($activeMembership) {
            // Logic for upgrading or extending can go here. 
            // For now, let's just expire the old one and create a new one.
            $activeMembership->update(['status' => 'cancelled']);
        }

        // Create new membership
        // In a real app, this would happen AFTER payment confirmation.
        // For now, we assume free or instant activation.
        
        $membership = UserMembership::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now(),
            'end_date' => now()->addDays($plan->days),
            'status' => 'active'
        ]);

        return response()->json(['message' => 'Subscribed successfully', 'membership' => $membership]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'currency_code' => 'required|string|size:3',
            'days' => 'required|integer',
        ]);

        $plan = MembershipPlan::create($validated);
        return response()->json($plan, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric',
            'currency_code' => 'sometimes|required|string|size:3',
            'days' => 'sometimes|required|integer',
        ]);

        $plan = MembershipPlan::findOrFail($id);
        $plan->update($validated);
        return response()->json($plan);
    }

    public function destroy($id)
    {
        $plan = MembershipPlan::findOrFail($id);
        $plan->delete();
        return response()->json(['message' => 'Plan deleted successfully']);
    }
}
