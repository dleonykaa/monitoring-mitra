<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\SurveyDashboardOverview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminDashboardController extends Controller
{
    /**
     * Sistem hanya mengenal tiga peran: admin, pegawai BPS, dan mitra.
     *
     * @var list<string>
     */
    private const ROLES = ['admin', 'pegawai_bps', 'mitra'];

    /**
     * Dashboard admin = ringkasan seluruh survei yang sama dengan dashboard pegawai.
     */
    public function dashboard(SurveyDashboardOverview $overview): View
    {
        $summary = $overview->build();

        return view('panel.dashboard', [
            ...$summary,
            'panelTitle' => 'Admin',
            'menuView' => 'panel.admin.menu',
            'base' => '/admin',
            'canManage' => true,
        ]);
    }

    public function users(Request $request): View
    {
        $data = $request->validate([
            'role' => ['nullable', Rule::in(['all', ...self::ROLES])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $selectedRole = $data['role'] ?? 'pegawai_bps';
        $search = trim($data['q'] ?? '');
        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')))
            ->latest();

        if ($selectedRole !== 'all') {
            $users->role($selectedRole);
        }

        return view('panel.admin.users', [
            'users' => $users->paginate(15)->withQueryString(),
            'roles' => Role::query()->where('guard_name', 'web')->whereIn('name', self::ROLES)->get(),
            'selectedRole' => $selectedRole,
            'search' => $search,
            'roleCounts' => [
                'admin' => User::query()->role('admin')->count(),
                'pegawai_bps' => User::query()->role('pegawai_bps')->count(),
                'mitra' => User::query()->role('mitra')->count(),
                'all' => User::query()->count(),
            ],
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $user = User::query()->create(collect($data)->except('role')->all() + ['is_active' => true]);
        $user->syncRoles([$data['role']]);

        $this->log($request, 'admin.user.create', 'Membuat user '.$user->email);

        return back();
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user->update(collect($data)->except(['role', 'password'])->when(
            filled($data['password'] ?? null),
            fn ($payload) => $payload->put('password', $data['password'])
        )->all());
        $user->syncRoles([$data['role']]);

        $this->log($request, 'admin.user.update', 'Memperbarui user '.$user->email);

        return back();
    }

    public function deleteUser(Request $request, User $user): RedirectResponse
    {
        $email = $user->email;
        $user->delete();

        $this->log($request, 'admin.user.delete', 'Menghapus user '.$email);

        return back();
    }

    public function logs(Request $request): View
    {
        $query = ActivityLog::query()
            ->with('user')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->string('action')->value().'%'))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date('date')));

        return view('panel.admin.logs', [
            'logs' => $query->latest()->paginate(30)->withQueryString(),
            'users' => User::query()->orderBy('name')->get(),
            'filters' => $request->only(['user_id', 'action', 'date']),
        ]);
    }

    private function log(Request $request, string $action, string $description): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
