<?php

namespace App\Http\Controllers;

use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSlideController extends Controller
{
    public function index()
    {
        $slides = Slide::orderByDesc('id')->get();

        $data = $slides->map(function (Slide $slide) {
            $imageUrl = null;
            if ($slide->image) {
                $imageUrl = asset('storage/slides/' . $slide->image);
            }

            return [
                'id' => $slide->id,
                'title' => $slide->title,
                'button_title' => $slide->button_title ?? '',
                'image' => $imageUrl,
                'status' => (bool) $slide->status,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function active()
    {
        $slides = Slide::where('status', true)
            ->orderByDesc('id')
            ->get();

        $data = $slides->map(function (Slide $slide) {
            $imageUrl = null;
            if ($slide->image) {
                $imageUrl = asset('storage/slides/' . $slide->image);
            }

            return [
                'id' => $slide->id,
                'title' => $slide->title,
                'button_title' => $slide->button_title ?? '',
                'image' => $imageUrl,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'button_title' => 'nullable|string|max:255',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id' => 'nullable|integer|exists:slides,id',
        ]);

        if (!empty($validated['id'])) {
            $slide = Slide::findOrFail($validated['id']);
        } else {
            $slide = new Slide();
            $slide->status = true;
        }

        $slide->title = $validated['title'];
        $slide->button_title = $validated['button_title'] ?? null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('slides', $filename, 'public');
            $slide->image = basename($path);
        }

        $slide->save();

        return response()->json([
            'status' => true,
            'msg' => 'Slide saved successfully',
        ]);
    }

    public function toggle($id)
    {
        $slide = Slide::findOrFail($id);
        $slide->status = !$slide->status;
        $slide->save();

        return response()->json([
            'status' => true,
            'msg' => 'Status updated successfully',
            'data' => ['status' => (bool) $slide->status],
        ]);
    }

    public function destroy($id)
    {
        $slide = Slide::findOrFail($id);

        if ($slide->image) {
            Storage::disk('public')->delete('slides/' . $slide->image);
        }

        $slide->delete();

        return response()->json([
            'status' => true,
            'msg' => 'Slide deleted successfully',
        ]);
    }
}
