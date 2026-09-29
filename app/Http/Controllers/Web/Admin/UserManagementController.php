<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::query()->with('roles', 'teams')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'role' => ['required', 'string'],
        ]);

        $user = User::query()->create($data);
        $user->syncRoles([$data['role']]);

        ActivityLog::query()->create(['user_id' => $request->user()?->id, 'action' => 'user.create', 'description' => 'Create user '.$user->email]);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$user->id],
            'is_active' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'string'],
        ]);

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json($user->load('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User dihapus.']);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => $request->string('password')->value()]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }
}
