<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PengaduanAdminController extends Controller
{
    private const KATEGORI = [
        'Pelayanan',
        'Infrastruktur',
        'Keamanan',
        'Bansos',
        'Lingkungan',
        'Lainnya',
    ];

    private const STATUS = ['menunggu', 'diproses', 'selesai', 'ditolak'];

    public function index(Request $request)
    {
        $pengaduans = Pengaduan::with('warga')->latest()->paginate(15);
        return view('admin.pengaduan.index', compact('pengaduans'));
    }

    public function show(Request $request, $id)
    {
        $pengaduan = Pengaduan::with(['warga', 'penanggap'])->findOrFail($id);
        return view('admin.pengaduan.tanggapi', compact('pengaduan'));
    }

    public function tanggapi(Request $request, $id)
    {
        // Berikan respon/tanggapan tertulis dari admin dan ubah status aduan
    }

    public function updateStatus(Request $request, $id): RedirectResponse
    {
        $this->pastikanAdmin($request);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(self::STATUS)],
        ], [
            'status.required' => 'Status pengaduan wajib dipilih.',
            'status.in' => 'Status pengaduan tidak valid.',
        ]);

        $pengaduan = Pengaduan::findOrFail($id);
        $pengaduan->update(['status' => $data['status']]);

        return back()->with('sukses', 'Status pengaduan berhasil diperbarui.');
    }

    private function pastikanAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->isAdmin(),
            403,
            'Akses pengelolaan pengaduan hanya tersedia untuk admin dan perangkat desa.'
        );
    }
}
