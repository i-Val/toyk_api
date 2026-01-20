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
            'authorization_url' => $data['authorization_url'] ?? null,
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
