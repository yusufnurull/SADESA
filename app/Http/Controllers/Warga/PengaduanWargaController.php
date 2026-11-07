<?php

namespace App\Http\Controllers\Warga;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;

class PengaduanWargaController extends Controller
{
    public function index(Request $request)
    {
        $pengguna = $request->user();

        if (! $pengguna?->isWarga() || ! $pengguna->warga_id) {
            abort(403, 'Riwayat pengaduan hanya dapat diakses oleh akun warga yang terhubung dengan data warga.');
        }

        $wargaId = $pengguna->warga_id;
        $pengaduans = Pengaduan::where('warga_id', $wargaId)
            ->latest()
            ->paginate(10);

        return view('warga.pengaduan.index', compact('pengaduans'));
    }

    public function show(Request $request, $id)
    {
        $pengguna = $request->user();

        if (! $pengguna?->isWarga() || ! $pengguna->warga_id) {
            abort(403, 'Riwayat pengaduan hanya dapat diakses oleh akun warga yang terhubung dengan data warga.');
        }

        $wargaId = $pengguna->warga_id;
        $pengaduan = Pengaduan::where('warga_id', $wargaId)->findOrFail($id);

        return view('warga.pengaduan.show', compact('pengaduan'));
    }
}
