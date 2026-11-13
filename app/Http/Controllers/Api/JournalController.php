<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $journals = $request->user()->journals()
            ->withCount(['entries' => function ($q) {
                $q->whereNull('deleted_at');
            }])
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $journals,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color_hex' => 'nullable|string|max:10',
            'icon' => 'nullable|string|max:50',
        ]);

        $journal = Journal::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'color_hex' => $validated['color_hex'] ?? '#4F46E5',
            'icon' => $validated['icon'] ?? 'book',
            'is_default' => false,
        ]);

        $journal->loadCount('entries');

        return response()->json([
            'status' => 'success',
            'message' => 'Buku jurnal berhasil dibuat',
            'data' => $journal,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $journal = $request->user()->journals()
            ->withCount(['entries' => function ($q) {
                $q->whereNull('deleted_at');
            }])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $journal,
        ]);
    }

    public function update(Request $request, $id)
    {
        $journal = $request->user()->journals()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'color_hex' => 'sometimes|required|string|max:10',
            'icon' => 'nullable|string|max:50',
        ]);

        $journal->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Buku jurnal berhasil diperbarui',
            'data' => $journal,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $journal = $request->user()->journals()->findOrFail($id);

        if ($journal->is_default) {
            return response()->json([
                'status' => 'error',
                'message' => 'Buku jurnal utama tidak dapat dihapus.',
            ], 422);
        }

        // Move entries to default journal or cascade
        $defaultJournal = $request->user()->journals()->where('is_default', true)->first();
        if ($defaultJournal) {
            $journal->entries()->update(['journal_id' => $defaultJournal->id]);
        }

        $journal->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Buku jurnal berhasil dihapus dan catatan dipindahkan ke jurnal utama.',
        ]);
    }
}

