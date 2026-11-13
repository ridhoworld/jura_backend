<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EntryController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\MoodController;
use App\Http\Controllers\Api\PromptController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TemplateController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/pin-lock', [AuthController::class, 'setPinLock']);
        Route::post('/verify-pin', [AuthController::class, 'verifyPinLock']);
    });

    // Journals (Buku)
    Route::apiResource('journals', JournalController::class);

    // Entries (Catatan Jurnal)
    Route::get('/entries/trash', [EntryController::class, 'trash']);
    Route::get('/entries/export', [EntryController::class, 'export']);
    Route::post('/entries/{id}/restore', [EntryController::class, 'restore']);
    Route::delete('/entries/{id}/force', [EntryController::class, 'forceDelete']);
    Route::post('/entries/{id}/toggle-favorite', [EntryController::class, 'toggleFavorite']);
    Route::post('/entries/{id}/toggle-pin', [EntryController::class, 'togglePin']);
    Route::apiResource('entries', EntryController::class);

    // Moods
    Route::get('/moods', [MoodController::class, 'index']);
    Route::post('/moods', [MoodController::class, 'store']);
    Route::get('/moods/stats', [MoodController::class, 'stats']);

    // Tags
    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);

    // Prompts
    Route::get('/prompts', [PromptController::class, 'index']);

    // Templates
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);

    // Reminders
    Route::get('/reminders', [ReminderController::class, 'index']);
    Route::post('/reminders', [ReminderController::class, 'store']);
    Route::patch('/reminders/{id}/toggle', [ReminderController::class, 'toggle']);
    Route::delete('/reminders/{id}', [ReminderController::class, 'destroy']);

    // Media / Attachments Upload
    Route::post('/attachments/upload', [AttachmentController::class, 'upload']);
});
