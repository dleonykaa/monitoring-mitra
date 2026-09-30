<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SurveyEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Foto bukti pencacahan disimpan di disk privat dan hanya disajikan lewat alamat ini:
 * admin dan pegawai boleh melihat semua foto, mitra hanya foto entrinya sendiri.
 */
class EvidencePhotoController extends Controller
{
    public function show(Request $request, SurveyEntry $entry): StreamedResponse
    {
        $user = $request->user();
        $ownsEntry = $entry->assignment()->where('mitra_id', $user->id)->exists();
        abort_unless($user->hasAnyRole(['admin', 'pegawai_bps']) || $ownsEntry, 403);

        $disk = Storage::disk(SurveyEntry::PHOTO_DISK);
        abort_unless(filled($entry->evidence_photo_path) && $disk->exists($entry->evidence_photo_path), 404);

        return $disk->response($entry->evidence_photo_path, null, ['Cache-Control' => 'private, max-age=3600']);
    }
}
