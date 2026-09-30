<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MitraSurveyHoldings;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar mitra beserta survei yang sedang dan sudah mereka pegang.
 */
class MitraDirectoryController extends Controller
{
    public function index(Request $request, MitraSurveyHoldings $holdings): View
    {
        $search = trim($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '');

        $mitraUsers = User::query()
            ->role('mitra')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('panel.admin.mitra.index', [
            'mitraUsers' => $mitraUsers,
            'holdings' => $holdings->forMitra($mitraUsers->getCollection()),
            'search' => $search,
        ]);
    }

    public function show(User $user, MitraSurveyHoldings $holdings): View
    {
        abort_unless($user->hasRole('mitra'), 404);

        $items = $holdings->forMitra(collect([$user]))->get($user->id, collect());

        return view('panel.admin.mitra.show', [
            'mitra' => $user,
            'runningItems' => $items->filter(fn (array $item): bool => $item['survey']->status !== 'Selesai')->values(),
            'completedItems' => $items->filter(fn (array $item): bool => $item['survey']->status === 'Selesai')->values(),
        ]);
    }
}
