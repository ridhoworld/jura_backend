<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mood;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoodController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $moods = Mood::whereNull('user_id')
            ->orWhere('user_id', $userId)
            ->orderBy('score', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $moods,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'emoji' => 'required|string|max:10',
            'score' => 'required|integer|min:1|max:5',
        ]);

        $mood = Mood::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'emoji' => $validated['emoji'],
            'score' => $validated['score'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Mood kustom berhasil ditambahkan',
            'data' => $mood,
        ], 201);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m')); // e.g. "2026-11"

        // Daily average mood score in this month
        $dailyScores = DB::table('entries')
            ->join('entry_moods', 'entries.id', '=', 'entry_moods.entry_id')
            ->join('moods', 'entry_moods.mood_id', '=', 'moods.id')
            ->where('entries.user_id', $user->id)
            ->whereNull('entries.deleted_at')
            ->where('entries.entry_date', 'like', "{$month}%")
            ->selectRaw('DATE(entries.entry_date) as date, AVG(moods.score) as avg_score, COUNT(entries.id) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Mood breakdown frequency
        $breakdown = DB::table('entries')
            ->join('entry_moods', 'entries.id', '=', 'entry_moods.entry_id')
            ->join('moods', 'entry_moods.mood_id', '=', 'moods.id')
            ->where('entries.user_id', $user->id)
            ->whereNull('entries.deleted_at')
            ->where('entries.entry_date', 'like', "{$month}%")
            ->selectRaw('moods.name, moods.emoji, moods.score, COUNT(entry_moods.entry_id) as total')
            ->groupBy('moods.name', 'moods.emoji', 'moods.score')
            ->orderBy('total', 'desc')
            ->get();

        // Total entries written this month
        $totalEntries = $user->entries()
            ->where('entry_date', 'like', "{$month}%")
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'month' => $month,
                'total_entries' => $totalEntries,
                'daily_scores' => $dailyScores,
                'breakdown' => $breakdown,
            ],
        ]);
    }
}

