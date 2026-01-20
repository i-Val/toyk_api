<?php

namespace App\Http\Controllers;

use App\Models\HomeSetting;
use Illuminate\Http\Request;

class HomeSettingController extends Controller
{
    public function show()
    {
        $setting = HomeSetting::first();

        if (!$setting) {
            return response()->json([
                'status' => false,
                'msg' => 'Settings not found',
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'featured_enabled' => (bool) $setting->free_posts,
                'allow_sms_enabled' => (bool) $setting->allow_sms,
                'email' => $setting->email,
                'phone' => $setting->phone,
                'whatsapp' => $setting->w_phone,
                'address' => $setting->address,
                'facebook' => $setting->facebook,
                'twitter' => $setting->twitter,
                'instagram' => $setting->instagram,
                'youtube' => $setting->youtube,
                'linkedin' => $setting->linkdin,
                'android_version' => $setting->app_version,
                'ios_version' => $setting->ios_version,
                'top_categories' => $setting->top_categories ?? [],
                'nav_categories' => $setting->nav_categories ?? [],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'featured_enabled' => 'required|boolean',
            'allow_sms_enabled' => 'required|boolean',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'address' => 'nullable|string',
            'facebook' => 'nullable|string',
            'twitter' => 'nullable|string',
            'instagram' => 'nullable|string',
            'youtube' => 'nullable|string',
            'linkedin' => 'nullable|string',
            'android_version' => 'nullable|string',
            'ios_version' => 'nullable|string',
            'top_categories' => 'array',
            'top_categories.*' => 'string',
            'nav_categories' => 'array',
            'nav_categories.*' => 'string',
        ]);

        $setting = HomeSetting::first() ?? new HomeSetting();

        $setting->free_posts = $data['featured_enabled'] ? 1 : 0;
        $setting->allow_sms = $data['allow_sms_enabled'] ? 1 : 0;
        $setting->email = $data['email'] ?? null;
        $setting->phone = $data['phone'] ?? null;
        $setting->w_phone = $data['whatsapp'] ?? null;
        $setting->address = $data['address'] ?? null;
        $setting->facebook = $data['facebook'] ?? null;
        $setting->twitter = $data['twitter'] ?? null;
        $setting->instagram = $data['instagram'] ?? null;
        $setting->youtube = $data['youtube'] ?? null;
        $setting->linkdin = $data['linkedin'] ?? null;
        $setting->app_version = $data['android_version'] ?? null;
        $setting->ios_version = $data['ios_version'] ?? null;
        $setting->top_categories = $data['top_categories'] ?? [];
        $setting->nav_categories = $data['nav_categories'] ?? [];

        $setting->save();

        return response()->json([
            'status' => true,
            'msg' => 'Settings updated successfully',
        ]);
    }
}

