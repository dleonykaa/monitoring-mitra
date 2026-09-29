@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Data Entri'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-h" style="margin-bottom:10px"><span class="dot"></span> Pilih Survei</div>
        <form method="GET" action="/mitra/entries" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <select name="survey_id" style="min-width:300px;" onchange="this.form.submit()">
                <option value="">Pilih jenis survei</option>
                @foreach ($assignments as $assignment)
                    <option value="{{ $assignment->survey_id }}" @selected($selectedSurveyId === $assignment->survey_id)>{{ $assignment->survey->title }}</option>
                @endforeach
            </select>
            <button type="submit">Tampilkan</button>
            @if ($selectedAssignment)
                <a class="btn" href="/mitra/surveys/{{ $selectedAssignment->survey_id }}/entries/create" style="padding:9px 14px;">Tambah Progress</a>
            @endif
        </form>
    </div>

    @if (! $selectedAssignment)
        <div class="card" style="text-align:center;padding:44px 20px;">
            <div style="font-weight:800;font-size:17px;color:var(--brand-dark)">Pilih survei terlebih dahulu</div>
            <div class="muted" style="max-width:520px;margin:8px auto 0">
                Setiap survei memiliki variabel validasi dan daftar entri yang berbeda. Pilih survei untuk menampilkan data entri Anda.
            </div>
        </div>
    @else
        @php
            $progress = $selectedAssignment->target > 0 ? min(100, round(($selectedAssignment->current_progress / $selectedAssignment->target) * 100, 1)) : 0;
        @endphp
        <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
                <div style="font-weight:800;font-size:16px;color:var(--brand-dark)">{{ $selectedAssignment->survey->title }}</div>
                <div class="muted">{{ $entries->total() }} entri, progress {{ $selectedAssignment->current_progress }}/{{ $selectedAssignment->target }} ({{ $progress }}%)</div>
            </div>
            <div style="min-width:260px;display:grid;grid-template-columns:1fr 48px;gap:8px;align-items:center">
                <div class="bar"><i style="width:{{ $progress }}%"></i></div>
                <span class="muted">{{ $progress }}%</span>
            </div>
        </div>

        <div class="card" style="padding:0;overflow:hidden">
            @include('panel.mitra.entries-table', ['entries' => $entries, 'compact' => true])
        </div>

        <div class="card">{{ $entries->links() }}</div>
    @endif
@endsection

@include('panel.mitra.styles')
