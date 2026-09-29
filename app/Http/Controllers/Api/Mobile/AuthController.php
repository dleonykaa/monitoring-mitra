<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobileLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(MobileLoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password) || ! $user->hasRole('mitra') || ! $user->is_active) {
            return response()->json(['message' => 'Email atau password tidak valid.'], 422);
        }

        $token = $user->createToken('mobile-mitra')->plainTextToken;

        if ($request->filled('device_token')) {
            $user->deviceTokens()->updateOrCreate(
                ['device_token' => $request->string('device_token')->value()],
                ['platform' => 'android']
            );
        }

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('assignments.survey'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
