<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\AttachmentController;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| v1 — VERSIONED API
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Login (issues a token with abilities)
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Auth helpers
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);      // current token only
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);   // revoke all tokens

        // READ-only routes (require notes:read)
        Route::middleware('abilities:notes:read')->group(function () {
            Route::get('/notes', [NoteController::class, 'index']);
            Route::get('/notes/{note}', [NoteController::class, 'show']);
        });

        // WRITE routes (require notes:write)
        Route::middleware('abilities:notes:write')->group(function () {
            Route::post('/notes', [NoteController::class, 'store']);
            Route::put('/notes/{note}', [NoteController::class, 'update']);
            Route::delete('/notes/{note}', [NoteController::class, 'destroy']);
            Route::post('/notes/{id}/restore', [NoteController::class, 'restore']);
        });

        // Attachments (require attachments:write)
        Route::middleware('abilities:attachments:write')->group(function () {
            Route::post('/notes/{note}/attachments', [AttachmentController::class, 'store']);
            Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);
        });

        // Signed download (auth + signed URL)
        Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
            ->middleware('signed')
            ->name('attachments.download');
    });

    // Admin endpoints (must be authenticated AND admin ability)
    Route::prefix('admin')->middleware(['auth:sanctum', 'throttle:api', 'admin', 'abilities:admin'])->group(function () {
        Route::get('/users', function () {
            return \App\Models\User::query()
                ->select('id', 'name', 'email', 'is_admin', 'created_at')
                ->latest()->paginate(20);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Legacy (unversioned) — keep until clients move to /api/v1/...
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', fn(Request $r) => $r->user());
    Route::apiResource('notes', NoteController::class);
    Route::post('/notes/{id}/restore', [NoteController::class, 'restore']);

    Route::post('/notes/{note}/attachments', [AttachmentController::class, 'store']);
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->middleware('signed')->name('attachments.download.legacy');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);
});

// Simple health check
Route::get('/hello', function () {
    return response()->json([
        'message' => 'API is alive on Laravel 12+',
        'time' => now()->toIso8601String(),
    ]);
});
