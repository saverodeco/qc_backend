<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QcInspector;
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
     * GET /api/qc/inspectors?q=dedi
     *
     * Dipakai dropdown pencarian "QC Inspector" di layar Input Metrik QC.
     * Tanpa query q, balikin daftar teratas (urut nama) buat tampilan awal.
     */
    public function searchInspectors(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $inspectors = QcInspector::active()
            ->when($q !== '', fn ($query) => $query->where('nama', 'ilike', "%{$q}%"))
            ->orderBy('nama')
            ->limit(20)
            ->get(['id', 'nama']);

        return response()->json([
            'success' => true,
            'data' => $inspectors,
        ]);
    }

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
     *
     * NOTE: no_iml sekarang integer dan cuma unique BERSAMA id_mill di
     * database (bukan unique sendirian). Query ini masih cari by no_iml
     * saja — aman selama aplikasi ini cuma dipakai untuk satu id_mill.
     * Kalau nanti multi-mill, tambahkan filter id_mill di sini.
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
                    . $iml->ts_mod?->format('d-m-Y H:i') . '.',
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
     * Body: { mc, im, ot, qc_inspector }
     * Field disamakan dengan skema perusahaan: MC, IM (dulu IMP), OT,
     * dan QCInspector (satu-satunya inspector yang diadopsi dari 3 yang
     * ada di skema perusahaan).
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
            'im' => 'required|numeric|min:0|max:100',
            'ot' => 'required|numeric|min:0|max:100',
            'qc_inspector' => 'required|string|max:35',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Foto tetap wajib minimal 1 — sebagai dokumentasi kondisi material.
        $photoCount = QcPhoto::where('no_iml', $noIml)->count();

        if ($photoCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal 1 foto dokumentasi wajib diunggah sebelum menyimpan QC.',
            ], 422);
        }

        $iml->update([
            'mc' => $request->mc,
            'im' => $request->im,
            'ot' => $request->ot,
            'qc_inspector' => $request->qc_inspector,
            'mod_usr_id' => $request->qc_inspector,
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

        if ($iml->status === 'QC_DONE') {
            return response()->json([
                'success' => false,
                'message' => 'QC untuk IML ini sudah selesai, foto tidak bisa diubah lagi.',
            ], 409);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File foto tidak valid.',
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
     */
    public function deletePhoto(string $noIml, int $id): JsonResponse
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
                'message' => 'QC untuk IML ini sudah selesai, foto tidak bisa dihapus.',
            ], 409);
        }

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