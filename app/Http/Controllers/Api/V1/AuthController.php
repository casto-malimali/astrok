<?php

namespace App\Http\Controllers\Api\V1;


use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $data = $r->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        $abilities = $data['abilities'] ?? ['notes:read', 'notes:write', 'attachments:write'];
        $token = $user->createToken($data['device_name'] ?? 'api', $abilities)->plainTextToken;

        return response()->json(['token' => $token, 'abilities' => $abilities], 201);
    }

    public function me(Request $r)
    {
        return [
            'user' => $r->user(),
            'abilities' => $r->user()->currentAccessToken()?->abilities,
        ];
    }

    public function logout(Request $r)
    {
        $token = $r->user()?->currentAccessToken();

        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $r->user()?->currentAccessToken();

        $token?->delete();

        return response()->json([
            'message' => 'Logged out successfully from this device.'
        ], 204);
    }

    public function logoutAll(Request $r)
    {
        $r->user()->tokens()->delete();
        return response()->noContent();
    }
}
