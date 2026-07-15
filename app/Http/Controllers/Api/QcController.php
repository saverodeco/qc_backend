<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QcPhoto;
use App\Models\ReceivingIml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QcController extends Controller
{
    /**
     * GET /api/qc?tanggal=2025-09-02
     *
     * Daftar kendaraan/IML untuk tanggal janji tertentu, beserta ringkasan
     * jumlah yang belum & sudah QC. Dipakai layar "Daftar Kendaraan QC".
     */
    public function index(Request $request): JsonResponse
    {
        $tanggal = $request->query('tanggal', now()->toDateString());

        $query = ReceivingIml::whereDate('tanggal_janji', $tanggal);

        $list = (clone $query)
            ->orderBy('status')
            ->orderBy('no_iml')
            ->get(['no_iml', 'vendor', 'nama_material', 'status']);

        $belumQc = (clone $query)->whereIn('status', ['WAITING_QC', 'QC_IN_PROGRESS'])->count();
        $sudahQc = (clone $query)->where('status', 'QC_DONE')->count();

        return response()->json([
            'success' => true,
            'tanggal' => $tanggal,
            'belum_qc' => $belumQc,
            'sudah_qc' => $sudahQc,
            'data' => $list,
        ]);
    }

    /**
     * GET /api/qc/lookup/{no_iml}
     *
     * Dipanggil saat operator input/scan No IML.
     * Mengembalikan data auto-generate + status, sekaligus melakukan
     * validasi apakah IML ini memang siap untuk di-QC.
     */
    public function lookup(string $noIml): JsonResponse
    {
        $iml = ReceivingIml::with('photos')->where('no_iml', $noIml)->first();

        if (! $iml) {
            return response()->json([
                'success' => false,
                'message' => 'No IML tidak terdaftar.',
            ], 404);
        }

        if ($iml->status === 'QC_DONE') {
            return response()->json([
                'success' => false,
                'message' => 'QC sudah selesai pada '
                    . $iml->qc_at?->format('d-m-Y H:i') . '.',
                'data' => $iml,
            ], 409);
        }

        if (! in_array($iml->status, ['WAITING_QC', 'QC_IN_PROGRESS'])) {
            return response()->json([
                'success' => false,
                'message' => 'Material belum masuk area QC. Status saat ini: '
                    . $iml->status,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'data' => $iml,
        ]);
    }

    /**
     * POST /api/qc/{no_iml}/submit
     *
     * Body: { mc, imp, ot, qc_by }
     * Meng-update record yang sudah ada (bukan membuat baru).
     */
    public function submit(Request $request, string $noIml): JsonResponse
    {
        $iml = ReceivingIml::where('no_iml', $noIml)->first();

        if (! $iml) {
            return response()->json([
                'success' => false,
                'message' => 'No IML tidak terdaftar.',
            ], 404);
        }

        if ($iml->status === 'QC_DONE') {
            return response()->json([
                'success' => false,
                'message' => 'QC untuk IML ini sudah pernah diselesaikan.',
            ], 409);
        }

        $validator = Validator::make($request->all(), [
            'mc' => 'required|numeric|min:0|max:100',
            'imp' => 'required|numeric|min:0|max:100',
            'ot' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $photoCount = QcPhoto::where('no_iml', $noIml)->count();
        if ($photoCount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal 1 foto wajib diunggah sebelum menyimpan QC.',
            ], 422);
        }

        $iml->update([
            'mc' => $request->mc,
            'imp' => $request->imp,
            'ot' => $request->ot,
            'qc_at' => now(),
            'status' => 'QC_DONE',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'QC berhasil disimpan.',
            'data' => $iml->fresh('photos'),
        ]);
    }

    /**
     * POST /api/qc/{no_iml}/photos
     *
     * Upload satu foto per request (dipanggil berkali-kali dari frontend,
     * bukan sekali dengan banyak file, supaya kalau 1 foto gagal tidak
     * perlu ulang semua).
     */
    public function uploadPhoto(Request $request, string $noIml): JsonResponse
    {
        $iml = ReceivingIml::where('no_iml', $noIml)->first();

        if (! $iml) {
            return response()->json([
                'success' => false,
                'message' => 'No IML tidak terdaftar.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120', // max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $existingCount = QcPhoto::where('no_iml', $noIml)->count();
        if ($existingCount >= ReceivingIml::MAX_PHOTOS) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal ' . ReceivingIml::MAX_PHOTOS . ' foto per IML sudah tercapai.',
            ], 422);
        }

        $urutan = QcPhoto::where('no_iml', $noIml)->max('urutan_foto') + 1;

        $filename = $noIml . '_' . $urutan . '_' . Str::random(6)
            . '.' . $request->file('photo')->getClientOriginalExtension();

        $path = $request->file('photo')->storeAs(
            "qc_photos/{$noIml}",
            $filename,
            'public'
        );

        $photo = QcPhoto::create([
            'no_iml' => $noIml,
            'photo_path' => $path,
            'urutan_foto' => $urutan,
        ]);

        // IML masuk status QC_IN_PROGRESS begitu foto pertama diunggah
        if ($iml->status === 'WAITING_QC') {
            $iml->update(['status' => 'QC_IN_PROGRESS']);
        }

        return response()->json([
            'success' => true,
            'data' => $photo,
        ], 201);
    }

    /**
     * DELETE /api/qc/{no_iml}/photos/{id}
     *
     * Untuk menghapus foto yang salah upload sebelum QC disimpan final.
     */
    public function deletePhoto(string $noIml, int $id): JsonResponse
    {
        $photo = QcPhoto::where('no_iml', $noIml)->where('id', $id)->first();

        if (! $photo) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan.',
            ], 404);
        }

        Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Foto dihapus.',
        ]);
    }
}