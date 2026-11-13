<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // max 20MB
            'type' => 'nullable|string|in:image,audio,video,document',
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();

        // Detect type if not provided
        $type = $request->type;
        if (!$type) {
            if (str_starts_with($mime, 'image/')) $type = 'image';
            elseif (str_starts_with($mime, 'audio/')) $type = 'audio';
            elseif (str_starts_with($mime, 'video/')) $type = 'video';
            else $type = 'document';
        }

        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . ($extension ? ".{$extension}" : '');
        $path = $file->storeAs('attachments', $fileName, 'public');

        $url = asset('storage/' . $path);

        return response()->json([
            'status' => 'success',
            'data' => [
                'file_url' => $url,
                'file_type' => $type,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ],
        ], 201);
    }
}

