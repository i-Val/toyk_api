<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembershipPlanController extends Controller
{
    public function index()
    {
        return MembershipPlan::all();
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
