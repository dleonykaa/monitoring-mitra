<div style="overflow-x:auto">
    <table class="rich-table">
        <thead>
            <tr>
                <th>ID</th>
                @unless ($compact ?? false)<th>Survei</th>@endunless
                <th>No Urut Ruta</th>
                <th>Wilayah</th>
                <th>Status</th>
                <th>Catatan Validasi</th>
                <th>Update</th>
                <th style="text-align:center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                @php
                    $statusText = $entry->entry_status === 'draft' ? 'Draft' : ($entry->is_valid === null ? 'Menunggu' : ($entry->is_valid ? 'Valid' : 'Tidak Valid'));
                    $statusClass = $entry->entry_status === 'draft' ? 'pill-amber' : ($entry->is_valid === null ? 'pill-amber' : ($entry->is_valid ? 'pill-green' : 'pill-rose'));
                    $editable = $entry->entry_status === 'draft' || ($entry->entry_status === 'submitted' && $entry->is_valid !== true);
                @endphp
                <tr>
                    <td>#{{ $entry->id }}</td>
                    @unless ($compact ?? false)<td>{{ $entry->survey->title }}</td>@endunless
                    <td>{{ $entry->no_urut_ruta ?: '-' }}</td>
                    <td>{{ $entry->district?->name ?? '-' }}<br><span class="muted" style="font-size:11.5px">{{ $entry->village?->name ?? '-' }}</span></td>
                    <td><span class="pill {{ $statusClass }}">{{ $statusText }}</span></td>
                    <td style="min-width:220px">
                        @if ($entry->entry_status === 'submitted' && $entry->is_valid === false)
                            <div class="invalid-note">{{ $entry->note ?: 'Tidak ada catatan.' }}</div>
                            <div class="muted" style="font-size:11px;margin-top:4px">Perbaiki data lalu kirim ulang melalui tombol Edit.</div>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>{{ ($entry->submitted_at ?? $entry->updated_at)?->format('d/m/Y H:i') }}</td>
                    <td style="text-align:center;white-space:nowrap">
                        @if ($editable)
                            <a class="btn" href="/mitra/entries/{{ $entry->id }}/edit" style="padding:7px 12px">Edit</a>
                        @else
                            <span class="muted">Terkunci</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ ($compact ?? false) ? 7 : 8 }}" class="muted" style="text-align:center;padding:24px">Belum ada entri.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
