<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $newsletter = Newsletter::firstOrCreate(
            ['email' => $request->input('email')],
            ['status' => true]
        );

        if (!$newsletter->status) {
            $newsletter->update(['status' => true]);
        }

        return response()->json(['message' => 'Subscribed successfully']);
    }
}
