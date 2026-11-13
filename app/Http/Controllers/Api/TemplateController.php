<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = Template::where('is_system', true)
            ->orWhere('user_id', $request->user()->id)
            ->orderBy('is_system', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'content_structure' => 'required|string',
        ]);

        $template = Template::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'content_structure' => $validated['content_structure'],
            'is_system' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Template berhasil dibuat',
            'data' => $template,
        ], 201);
    }
}

