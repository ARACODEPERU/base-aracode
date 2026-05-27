<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SocialeventsEditorController extends Controller
{
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $file = $request->file('image');
        $fileName = 'editor_' . time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $path = Storage::disk('public')->putFileAs('uploads/socialevents/editor', $file, $fileName);

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }
}
