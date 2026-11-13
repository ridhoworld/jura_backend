<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $reminders = $request->user()->reminders()->orderBy('reminder_time', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $reminders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reminder_time' => 'required|string',
            'days_of_week' => 'required|array',
            'is_active' => 'nullable|boolean',
        ]);

        $reminder = Reminder::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'reminder_time' => $validated['reminder_time'],
            'days_of_week' => $validated['days_of_week'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $reminder,
        ], 201);
    }

    public function toggle(Request $request, $id)
    {
        $reminder = $request->user()->reminders()->findOrFail($id);
        $reminder->is_active = !$reminder->is_active;
        $reminder->save();

        return response()->json([
            'status' => 'success',
            'data' => $reminder,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $reminder = $request->user()->reminders()->findOrFail($id);
        $reminder->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pengingat dihapus',
        ]);
    }
}

