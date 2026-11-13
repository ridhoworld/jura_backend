<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->entries()
            ->with(['journal:id,name,color_hex,icon', 'moods', 'tags', 'attachments']);

        // Filter by journal
        if ($request->filled('journal_id')) {
            $query->where('journal_id', $request->journal_id);
        }

        // Filter by favorites
        if ($request->boolean('is_favorite')) {
            $query->where('is_favorite', true);
        }

        // Filter by pinned
        if ($request->boolean('is_pinned')) {
            $query->where('is_pinned', true);
        }

        // Filter by mood
        if ($request->filled('mood_id')) {
            $query->whereHas('moods', function ($q) use ($request) {
                $q->where('moods.id', $request->mood_id);
            });
        }

        // Filter by tag
        if ($request->filled('tag_id')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tag_id);
            });
        }

        // Search text in title or content
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date_to);
        }

        // Sort: pinned entries first, then entry_date desc
        $entries = $query->orderBy('is_pinned', 'desc')
            ->orderBy('entry_date', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $entries,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_id' => 'required|uuid|exists:journals,id',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'entry_date' => 'nullable|date',
            'location_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'weather_info' => 'nullable|array',
            'is_favorite' => 'nullable|boolean',
            'is_pinned' => 'nullable|boolean',
            'mood_ids' => 'nullable|array',
            'mood_ids.*' => 'integer|exists:moods,id',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:50',
            'attachments' => 'nullable|array',
        ]);

        $user = $request->user();

        // Check if journal belongs to user
        $journal = $user->journals()->findOrFail($validated['journal_id']);

        $entry = Entry::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'journal_id' => $journal->id,
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
            'entry_date' => $validated['entry_date'] ?? now(),
            'location_name' => $validated['location_name'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'weather_info' => $validated['weather_info'] ?? null,
            'is_favorite' => $validated['is_favorite'] ?? false,
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        // Attach moods
        if (!empty($validated['mood_ids'])) {
            $entry->moods()->sync($validated['mood_ids']);
        }

        // Attach or create tags
        if (!empty($validated['tag_names'])) {
            $tagIds = [];
            foreach ($validated['tag_names'] as $tagName) {
                $cleaned = trim($tagName);
                if (empty($cleaned)) continue;
                $tag = Tag::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $cleaned],
                    ['id' => (string) Str::uuid()]
                );
                $tagIds[] = $tag->id;
            }
            $entry->tags()->sync($tagIds);
        }

        // Attachments
        if (!empty($validated['attachments'])) {
            foreach ($validated['attachments'] as $idx => $att) {
                Attachment::create([
                    'id' => (string) Str::uuid(),
                    'entry_id' => $entry->id,
                    'file_type' => $att['file_type'] ?? 'image',
                    'file_url' => $att['file_url'],
                    'thumbnail_url' => $att['thumbnail_url'] ?? null,
                    'duration_seconds' => $att['duration_seconds'] ?? null,
                    'caption' => $att['caption'] ?? null,
                    'order_index' => $att['order_index'] ?? $idx,
                ]);
            }
        }

        $entry->load(['journal:id,name,color_hex,icon', 'moods', 'tags', 'attachments']);

        return response()->json([
            'status' => 'success',
            'message' => 'Entri jurnal berhasil disimpan',
            'data' => $entry,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $entry = $request->user()->entries()
            ->with(['journal:id,name,color_hex,icon', 'moods', 'tags', 'attachments'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $entry,
        ]);
    }

    public function update(Request $request, $id)
    {
        $entry = $request->user()->entries()->findOrFail($id);

        $validated = $request->validate([
            'journal_id' => 'sometimes|required|uuid|exists:journals,id',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'entry_date' => 'sometimes|required|date',
            'location_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'weather_info' => 'nullable|array',
            'is_favorite' => 'nullable|boolean',
            'is_pinned' => 'nullable|boolean',
            'mood_ids' => 'nullable|array',
            'mood_ids.*' => 'integer|exists:moods,id',
            'tag_names' => 'nullable|array',
            'tag_names.*' => 'string|max:50',
            'attachments' => 'nullable|array',
        ]);

        $entry->update($request->only([
            'journal_id', 'title', 'content', 'entry_date',
            'location_name', 'latitude', 'longitude', 'weather_info',
            'is_favorite', 'is_pinned'
        ]));

        if ($request->has('mood_ids')) {
            $entry->moods()->sync($validated['mood_ids'] ?? []);
        }

        if ($request->has('tag_names')) {
            $tagIds = [];
            foreach ($validated['tag_names'] ?? [] as $tagName) {
                $cleaned = trim($tagName);
                if (empty($cleaned)) continue;
                $tag = Tag::firstOrCreate(
                    ['user_id' => $request->user()->id, 'name' => $cleaned],
                    ['id' => (string) Str::uuid()]
                );
                $tagIds[] = $tag->id;
            }
            $entry->tags()->sync($tagIds);
        }

        if ($request->has('attachments')) {
            $entry->attachments()->delete();
            foreach ($validated['attachments'] ?? [] as $idx => $att) {
                Attachment::create([
                    'id' => (string) Str::uuid(),
                    'entry_id' => $entry->id,
                    'file_type' => $att['file_type'] ?? 'image',
                    'file_url' => $att['file_url'],
                    'thumbnail_url' => $att['thumbnail_url'] ?? null,
                    'duration_seconds' => $att['duration_seconds'] ?? null,
                    'caption' => $att['caption'] ?? null,
                    'order_index' => $att['order_index'] ?? $idx,
                ]);
            }
        }

        $entry->load(['journal:id,name,color_hex,icon', 'moods', 'tags', 'attachments']);

        return response()->json([
            'status' => 'success',
            'message' => 'Entri jurnal berhasil diperbarui',
            'data' => $entry,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $entry = $request->user()->entries()->findOrFail($id);
        $entry->delete(); // Soft delete into trash

        return response()->json([
            'status' => 'success',
            'message' => 'Entri dipindahkan ke tempat sampah',
        ]);
    }

    public function trash(Request $request)
    {
        $trashed = $request->user()->entries()
            ->onlyTrashed()
            ->with(['journal:id,name,color_hex', 'moods'])
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $trashed,
        ]);
    }

    public function restore(Request $request, $id)
    {
        $entry = $request->user()->entries()->onlyTrashed()->findOrFail($id);
        $entry->restore();

        return response()->json([
            'status' => 'success',
            'message' => 'Entri berhasil dipulihkan dari tempat sampah',
        ]);
    }

    public function forceDelete(Request $request, $id)
    {
        $entry = $request->user()->entries()->onlyTrashed()->findOrFail($id);
        $entry->forceDelete();

        return response()->json([
            'status' => 'success',
            'message' => 'Entri telah dihapus secara permanen',
        ]);
    }

    public function toggleFavorite(Request $request, $id)
    {
        $entry = $request->user()->entries()->findOrFail($id);
        $entry->is_favorite = !$entry->is_favorite;
        $entry->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $entry->id,
                'is_favorite' => $entry->is_favorite,
            ],
        ]);
    }

    public function togglePin(Request $request, $id)
    {
        $entry = $request->user()->entries()->findOrFail($id);
        $entry->is_pinned = !$entry->is_pinned;
        $entry->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $entry->id,
                'is_pinned' => $entry->is_pinned,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $entries = $request->user()->entries()
            ->with(['journal', 'moods', 'tags', 'attachments'])
            ->orderBy('entry_date', 'asc')
            ->get();

        $format = $request->query('format', 'json');

        if ($format === 'markdown') {
            $md = "# Ekspor Jurnal Jura - {$request->user()->full_name}\n\n";
            foreach ($entries as $e) {
                $date = $e->entry_date ? $e->entry_date->format('Y-m-d H:i') : '';
                $moodStr = $e->moods->pluck('name')->implode(', ');
                $tagStr = $e->tags->pluck('name')->map(fn($t) => "#{$t}")->implode(' ');

                $md .= "## {$e->title} ({$date})\n";
                $md .= "*Buku: {$e->journal->name} | Mood: {$moodStr}*\n";
                if ($tagStr) $md .= "*Tags: {$tagStr}*\n";
                $md .= "\n" . ($e->content ?? '') . "\n\n---\n\n";
            }

            return response($md, 200, [
                'Content-Type' => 'text/markdown',
                'Content-Disposition' => 'attachment; filename="jura_export.md"',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $entries,
        ]);
    }
}

