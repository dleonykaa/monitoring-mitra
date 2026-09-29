@extends('panel.layout', ['panelTitle' => 'Mitra BPS', 'pageTitle' => 'Survei Saya'])

@section('menu')
    @include('panel.mitra.menu')
@endsection

@section('content')
    @foreach ([['title' => 'Survei Berjalan', 'items' => $runningAssignments], ['title' => 'Survei Selesai', 'items' => $completedAssignments]] as $group)
        <div class="card">
            <div class="card-h"><span class="dot"></span> {{ $group['title'] }} ({{ $group['items']->count() }})</div>
            <div class="mitra-survey-list">
                @forelse ($group['items'] as $assignment)
                    @include('panel.mitra.survey-row', ['assignment' => $assignment])
                @empty
                    <div class="muted">Tidak ada {{ strtolower($group['title']) }}.</div>
                @endforelse
            </div>
        </div>
    @endforeach
@endsection

@include('panel.mitra.styles')
