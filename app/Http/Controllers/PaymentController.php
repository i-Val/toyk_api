<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = $request->user()->payments()->latest()->get();
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        // This would normally involve interacting with a payment gateway
        // For now, we'll just create a record
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $payment = $request->user()->payments()->create([
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'description' => $validated['description'],
            'status' => 'pending', // In a real app, this would be determined by the gateway
            'currency' => 'USD'
        ]);

        return response()->json($payment, 201);
    }
}
