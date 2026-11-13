<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'pin_lock_hash' => 'nullable|string',
        ]);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'full_name' => $validated['full_name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'pin_lock_hash' => !empty($validated['pin_lock_hash']) ? Hash::make($validated['pin_lock_hash']) : null,
            'encryption_salt' => Str::random(16),
        ]);

        // Auto-create default journal as per PRD FR-01
        $defaultJournal = Journal::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Jurnal Pribadi',
            'color_hex' => '#4F46E5',
            'icon' => 'book',
            'is_default' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'default_journal' => $defaultJournal,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kombinasi email atau kata sandi tidak cocok.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $defaultJournal = $user->journals()->where('is_default', true)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'default_journal' => $defaultJournal,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $defaultJournal = $user->journals()->where('is_default', true)->first();
        $journalCount = $user->journals()->count();
        $entryCount = $user->entries()->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'default_journal' => $defaultJournal,
                'stats' => [
                    'journals_count' => $journalCount,
                    'entries_count' => $entryCount,
                ],
            ],
        ]);
    }

    public function setPinLock(Request $request)
    {
        $request->validate([
            'pin' => 'nullable|string|min:4|max:6',
        ]);

        $user = $request->user();
        $user->pin_lock_hash = $request->pin ? Hash::make($request->pin) : null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => $request->pin ? 'Kunci PIN berhasil diaktifkan' : 'Kunci PIN dinonaktifkan',
        ]);
    }

    public function verifyPinLock(Request $request)
    {
        $request->validate([
            'pin' => 'required|string',
        ]);

        $user = $request->user();
        if (! $user->pin_lock_hash || ! Hash::check($request->pin, $user->pin_lock_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN salah',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'PIN terverifikasi',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ]);
    }
}

