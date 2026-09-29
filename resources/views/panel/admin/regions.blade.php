@extends('panel.layout', ['panelTitle' => 'Admin', 'pageTitle' => 'Wilayah'])

@section('menu')
    @include('panel.admin.menu')
@endsection

@section('content')
    <div class="grid g2">
        <div class="card">
            <div class="card-h"><span class="dot"></span>Tambah Kecamatan</div>
            <form method="POST" action="/admin/regions/districts" style="display:flex;flex-wrap:wrap;gap:8px;">
                @csrf
                <input name="name" placeholder="Nama Kecamatan" required style="flex:1 1 160px;min-width:0;">
                <button type="submit" style="flex:0 0 auto;">Tambah</button>
            </form>
        </div>

        <div class="card">
            <div class="card-h"><span class="dot"></span>Tambah Kelurahan / Pulau</div>
            <form method="POST" action="/admin/regions/villages" style="display:flex;flex-wrap:wrap;gap:8px;">
                @csrf
                <select name="district_id" style="flex:1 1 150px;min-width:0;">
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                    @endforeach
                </select>
                <input name="name" placeholder="Nama Kelurahan/Pulau" required style="flex:2 1 180px;min-width:0;">
                <select name="type" style="flex:1 1 120px;min-width:0;">
                    <option value="kelurahan">Kelurahan</option>
                    <option value="pulau">Pulau</option>
                </select>
                <button type="submit" style="flex:0 0 auto;">Tambah</button>
            </form>
        </div>
    </div>

    @foreach ($districts as $district)
        <div class="card">
            <form method="POST" action="/admin/regions/districts/{{ $district->id }}" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:12px;">
                @csrf
                @method('PUT')
                <input name="name" value="{{ $district->name }}" required style="font-weight:700;flex:1 1 160px;min-width:0;">
                <button type="submit" style="flex:0 0 auto;">Simpan Kecamatan</button>
            </form>

            <div class="summary-grid">
                @forelse ($district->villages as $village)
                    <div class="summary-item">
                        <form method="POST" action="/admin/regions/villages/{{ $village->id }}" style="display:grid;gap:8px;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="district_id" value="{{ $district->id }}">
                            <input name="name" value="{{ $village->name }}" required>
                            <select name="type">
                                <option value="kelurahan" @selected($village->type === 'kelurahan')>Kelurahan</option>
                                <option value="pulau" @selected($village->type === 'pulau')>Pulau</option>
                            </select>
                            <button type="submit">Simpan</button>
                        </form>
                        <form method="POST" action="/admin/regions/villages/{{ $village->id }}" onsubmit="return confirm('Hapus wilayah ini?')" style="margin-top:8px;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background:#be123c;">Hapus</button>
                        </form>
                    </div>
                @empty
                    <div class="muted">Belum ada kelurahan/pulau.</div>
                @endforelse
            </div>
        </div>
    @endforeach
@endsection
