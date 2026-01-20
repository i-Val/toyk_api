<?php

namespace App\Http\Controllers;

use App\Models\SplashMessage;
use Illuminate\Http\Request;

class SplashMessageController extends Controller
{
    public function show()
    {
        $message = SplashMessage::first();

        if (!$message) {
            return response()->json([
                'status' => false,
                'msg' => 'Splash message not found',
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $message->id,
                'web_message' => $message->web_message,
                'web_link' => $message->web_link,
                'app_message' => $message->app_message,
                'messgae_bg_color' => $message->messgae_bg_color,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'web_message' => 'nullable|string',
            'web_link' => 'nullable|string',
            'app_message' => 'nullable|string|max:241',
            'messgae_bg_color' => 'nullable|in:red,blue',
        ]);

        $message = SplashMessage::first() ?? new SplashMessage();

        $message->web_message = $data['web_message'] ?? null;
        $message->web_link = $data['web_link'] ?? null;
        $message->app_message = $data['app_message'] ?? null;
        $message->messgae_bg_color = $data['messgae_bg_color'] ?? null;

        $message->save();

        return response()->json([
            'status' => true,
            'msg' => 'Splash message updated successfully',
        ]);
    }
}

