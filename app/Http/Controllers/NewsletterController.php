<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function adminIndex(Request $request)
    {
        $query = Newsletter::query()->latest();

        if ($search = $request->query('search')) {
            $query->where('email', 'like', '%' . $search . '%');
        }

        $paginator = $query->paginate(20);

        return response()->json([
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'total_subscribers' => Newsletter::count(),
        ]);
    }

    public function toggleStatus($id)
    {
        $newsletter = Newsletter::findOrFail($id);
        $newsletter->status = !$newsletter->status;
        $newsletter->save();

        return response()->json([
            'status' => true,
            'msg' => 'Status updated successfully',
            'data' => [
                'status' => (bool) $newsletter->status,
            ],
        ]);
    }

    public function destroy($id)
    {
        $newsletter = Newsletter::findOrFail($id);
        $newsletter->delete();

        return response()->json([
            'message' => 'Subscriber deleted successfully',
        ]);
    }

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
