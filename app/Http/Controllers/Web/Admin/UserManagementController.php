<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class UserManagementController extends Controller
{
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse|RedirectResponse
    {
        $request->user()->update(['password' => $request->string('password')->value()]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Password berhasil diubah.']);
        }

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}
