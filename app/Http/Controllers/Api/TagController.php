<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = $request->user()->tags()
            ->withCount('entries')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $tag = Tag::firstOrCreate(
            ['user_id' => $request->user()->id, 'name' => trim($validated['name'])],
            ['id' => (string) Str::uuid()]
        );

        return response()->json([
            'status' => 'success',
            'data' => $tag,
        ], 201);
    }
}

