<?php

use App\Http\Controllers\AttachmentController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;
use Illuminate\Validation\ValidationException;



Route::post('/login', function (Request $r) {
    $cred = $r->validate([
        'email' => 'required|email',
        'password' => 'required|string',
        'device_name' => 'nullable|string|max:60',
    ]);
    $user = User::where('email', $cred['email'])->first();
    if (!$user || !Hash::check($cred['password'], $user->password)) {
        throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
    }
    $token = $user->createToken($cred['device_name'] ?? 'api', ['*'])->plainTextToken;
    return response()->json(['token' => $token], 201);
})->middleware('throttle:login');



Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', fn(Request $r) => $r->user());
    // Route::post('/logout', fn(Request $r) => tap($r->user()->currentAccessToken()?->delete(), fn() => null) ?? response()->noContent());
    Route::post('/logout', function (Request $r) {
        $r->user()->tokens()->delete();
        return response()->noContent();
    });
    Route::get('/notes', [NoteController::class, 'index']);
    Route::get('/notes/{note}', [NoteController::class, 'show']);
    Route::middleware('throttle:notes-write')->group(function () {
        // Route::post('/notes', [NoteController::class, 'store']);
        // Route::put('/notes/{note}', [NoteController::class, 'update']);
        // Route::delete('/notes/{note}', [NoteController::class, 'destroy']);
        Route::apiResource('notes', \App\Http\Controllers\NoteController::class);
        Route::post('/notes/{id}/restore', [\App\Http\Controllers\NoteController::class, 'restore']);
    });
    Route::post('/notes/{note}/attachments', [AttachmentController::class, 'store']);
    // Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
    //     ->middleware('signed'); // signed URL required
    Route::get('/attachments/{attachment}/download', [\App\Http\Controllers\AttachmentController::class, 'download'])
        ->middleware('signed')
        ->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);
});




Route::get('/hello', function () {
    return response()->json([
        'message' => 'API is alive on Laravel 12+',
        'time' => now()->toIso8601String(),
    ]);
});


