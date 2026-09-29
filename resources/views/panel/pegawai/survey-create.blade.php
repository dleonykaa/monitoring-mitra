@extends('panel.layout', ['panelTitle' => 'Pegawai BPS', 'pageTitle' => 'Tambah Survei'])

@section('menu')
    @include('panel.pegawai.menu')
@endsection

@section('content')
    <div class="card">
        <div class="card-h"><span class="dot"></span>Form Survei Baru</div>
        <form method="POST" action="/pegawai/surveys" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Tim Kerja
                <select name="team_id" required style="width:100%;margin-top:4px;">
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Judul Survei
                <input name="title" value="{{ old('title') }}" required style="width:100%;margin-top:4px;">
            </label>
            <label>Tanggal Mulai
                <input type="date" name="start_date" id="startDate" value="{{ old('start_date') }}" min="{{ now()->toDateString() }}" required style="width:100%;margin-top:4px;">
            </label>
            <label>Tanggal Berakhir
                <input type="date" name="end_date" id="endDate" value="{{ old('end_date') }}" min="{{ now()->toDateString() }}" required style="width:100%;margin-top:4px;">
            </label>
            <label style="grid-column:1/-1;">Deskripsi
                <textarea name="description" rows="4" style="width:100%;margin-top:4px;">{{ old('description') }}</textarea>
            </label>
            <div style="grid-column:1/-1;">
                <div class="muted" style="margin-bottom:8px">ℹ️ Total target akan dihitung otomatis dari akumulasi target tiap mitra pada langkah alokasi. Survei disimpan sebagai <b>Draft</b> sampai Anda menjalankannya.</div>
                <div style="display:flex;gap:8px;">
                    <button type="submit">Simpan dan Lanjutkan</button>
                    <a class="btn btn-grey" href="/pegawai/surveys">Batal</a>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('startDate').addEventListener('change', function () {
    const endDate = document.getElementById('endDate');
    endDate.min = this.value;
    if (endDate.value && endDate.value < this.value) {
        endDate.value = this.value;
    }
});
</script>
@endpush
