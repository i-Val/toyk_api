<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function followers(Request $request)
    {
        $followers = $request->user()->followers;
        return response()->json($followers);
    }

    public function following(Request $request)
    {
        $following = $request->user()->following;
        return response()->json($following);
    }

    public function follow(Request $request, $id)
    {
        $userToFollow = User::findOrFail($id);
        $currentUser = $request->user();

        if ($currentUser->id === $userToFollow->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        if (!$currentUser->following()->where('user_id', $userToFollow->id)->exists()) {
            $currentUser->following()->attach($userToFollow->id);
            return response()->json(['message' => 'Followed successfully']);
        }

        return response()->json(['message' => 'Already following'], 400);
    }

    public function unfollow(Request $request, $id)
    {
        $userToUnfollow = User::findOrFail($id);
        $currentUser = $request->user();

        if ($currentUser->following()->where('user_id', $userToUnfollow->id)->exists()) {
            $currentUser->following()->detach($userToUnfollow->id);
            return response()->json(['message' => 'Unfollowed successfully']);
        }

        return response()->json(['message' => 'Not following'], 400);
    }
}
