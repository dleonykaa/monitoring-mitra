<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\District;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\Team;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminDashboardController extends Controller
{
    public function dashboard(): View
    {
        return view('panel.admin.dashboard', [
            'totalUsers' => User::query()->count(),
            'totalSurveys' => Survey::query()->count(),
            'totalActivities' => ActivityLog::query()->count(),
            'totalActiveUsers' => User::query()->where('is_active', true)->count(),
        ]);
    }

    public function users(Request $request): View
    {
        $data = $request->validate([
            'role' => ['nullable', 'in:all,pegawai_bps,mitra'],
        ]);
        $selectedRole = $data['role'] ?? 'pegawai_bps';
        $users = User::query()->with('roles')->latest();

        if ($selectedRole !== 'all') {
            $users->role($selectedRole);
        }

        return view('panel.admin.users', [
            'users' => $users->paginate(15)->withQueryString(),
            'roles' => Role::query()->where('guard_name', 'web')->get(),
            'selectedRole' => $selectedRole,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'exists:roles,name'],
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
            'role' => ['required', 'exists:roles,name'],
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

    public function roles(): View
    {
        return view('panel.admin.roles', [
            'roles' => Role::query()->with('permissions')->where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function updateRolePermissions(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        $this->log($request, 'admin.role.update', 'Memperbarui hak akses role '.$role->name);

        return back()->with('status', 'Hak akses role diperbarui.');
    }

    public function deleteUser(Request $request, User $user): RedirectResponse
    {
        $email = $user->email;
        $user->delete();

        $this->log($request, 'admin.user.delete', 'Menghapus user '.$email);

        return back();
    }

    public function teams(): View
    {
        return view('panel.admin.teams', [
            'teams' => Team::query()->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function storeTeam(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Team::query()->create($data);
        $this->log($request, 'admin.team.create', 'Membuat tim '.$data['name']);

        return back()->with('status', 'Tim kerja ditambahkan.');
    }

    public function teamDetail(Team $team): View
    {
        return view('panel.admin.team-detail', [
            'team' => $team->load('users'),
            'pegawaiUsers' => User::query()->role('pegawai_bps')->orderBy('name')->get(),
        ]);
    }

    public function assignPegawai(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $team->users()->syncWithoutDetaching([$data['user_id']]);
        $this->log($request, 'admin.team.assign', 'Alokasi pegawai ke tim '.$team->name);

        return back()->with('status', 'Pegawai dialokasikan ke tim.');
    }

    public function detachPegawai(Request $request, Team $team, User $user): RedirectResponse
    {
        $team->users()->detach($user->id);
        $this->log($request, 'admin.team.detach', 'Melepas pegawai dari tim '.$team->name);

        return back()->with('status', 'Pegawai dilepas dari tim.');
    }

    public function regions(): View
    {
        return view('panel.admin.regions', [
            'districts' => District::query()->with('villages')->orderBy('name')->get(),
        ]);
    }

    public function storeDistrict(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'unique:districts,name']]);
        District::query()->create($data);
        $this->log($request, 'admin.region.district', 'Menambah kecamatan '.$data['name']);

        return back();
    }

    public function updateDistrict(Request $request, District $district): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'unique:districts,name,'.$district->id]]);
        $district->update($data);
        $this->log($request, 'admin.region.district.update', 'Mengubah kecamatan '.$district->name);

        return back();
    }

    public function storeVillage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'district_id' => ['required', 'exists:districts,id'],
            'name' => ['required', 'string'],
            'type' => ['required', 'string'],
        ]);

        Village::query()->create($data);
        $this->log($request, 'admin.region.village', 'Menambah wilayah '.$data['name']);

        return back();
    }

    public function updateVillage(Request $request, Village $village): RedirectResponse
    {
        $data = $request->validate([
            'district_id' => ['required', 'exists:districts,id'],
            'name' => ['required', 'string'],
            'type' => ['required', 'string'],
        ]);

        $village->update($data);
        $this->log($request, 'admin.region.village.update', 'Mengubah wilayah '.$village->name);

        return back();
    }

    public function deleteVillage(Request $request, Village $village): RedirectResponse
    {
        $name = $village->name;
        $village->delete();
        $this->log($request, 'admin.region.village.delete', 'Menghapus wilayah '.$name);

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

    public function monitoringSurveys(): View
    {
        return view('panel.admin.monitoring-surveys', [
            'surveys' => Survey::query()->withCount(['entries', 'assignments'])->latest()->paginate(20),
        ]);
    }

    public function monitoringSurveyDetail(Survey $survey): View
    {
        $survey->load(['assignments.mitra', 'entries']);

        return view('panel.admin.monitoring-survey-detail', compact('survey'));
    }

    public function monitoringWilayah(): View
    {
        return view('panel.admin.monitoring-wilayah', [
            'districtProgress' => District::query()->withCount('entries')->orderBy('name')->get(),
        ]);
    }

    public function monitoringKinerja(): View
    {
        return view('panel.admin.monitoring-kinerja', [
            'rankings' => SurveyAssignment::query()->with(['mitra', 'survey'])->orderByDesc('current_progress')->paginate(20),
        ]);
    }

    public function monitoringMitraProfile(User $user): View
    {
        $user->load('assignments.survey');

        return view('panel.admin.monitoring-mitra-profile', ['mitra' => $user]);
    }

    public function exportKinerja()
    {
        $rows = SurveyAssignment::query()->with(['mitra', 'survey'])->get()->map(function (SurveyAssignment $item): array {
            $percentage = $item->target > 0 ? round(($item->current_progress / $item->target) * 100, 2) : 0;

            return [
                'Mitra' => $item->mitra->name,
                'Survei' => $item->survey->title,
                'Progress' => $item->current_progress,
                'Target' => $item->target,
                'Persen' => $percentage,
                'Kategori' => $percentage >= 80 ? 'Baik' : ($percentage >= 60 ? 'Cukup' : 'Buruk'),
            ];
        });

        return Excel::download(new \App\Exports\EntriesExport($rows, ['Mitra', 'Survei', 'Progress', 'Target', 'Persen', 'Kategori']), 'laporan-kinerja-mitra.xlsx');
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
