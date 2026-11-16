<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;

class PengaduanPublicController extends Controller
{
    private const KATEGORI = [
        'Pelayanan',
        'Infrastruktur',
        'Keamanan',
        'Bansos',
        'Lingkungan',
        'Lainnya',
    ];

    public function create()
    {
        return view('public.pengaduan.create');
    }

    public function store(Request $request)
    {
        // Validasi, generate kode tiket pengaduan unik, upload foto bukti
    }

    public function lacak(Request $request)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:30'],
        ], [
            'kode.string' => 'Kode tiket harus berupa teks.',
            'kode.max' => 'Kode tiket tidak valid.',
        ]);

        $kodeTiket = isset($data['kode']) ? strtoupper(trim($data['kode'])) : null;
        $pengaduan = $kodeTiket
            ? Pengaduan::query()
                ->select([
                    'id',
                    'kode_pengaduan',
                    'kategori',
                    'judul_pengaduan',
                    'status',
                    'tanggapan',
                    'tanggal_tanggapan',
                    'created_at',
                ])
                ->where('kode_pengaduan', $kodeTiket)
                ->first()
            : null;

        return view('public.pengaduan.lacak', compact('pengaduan', 'kodeTiket'));
    }
}
