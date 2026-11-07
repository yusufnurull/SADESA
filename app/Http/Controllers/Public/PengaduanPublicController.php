<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

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
        $data = $request->validate([
            'nama_pelapor' => ['required', 'string', 'max:100'],
            'no_hp' => ['required', 'string', 'max:25'],
            'kategori' => ['required', 'string', Rule::in(self::KATEGORI)],
            'judul_pengaduan' => ['required', 'string', 'max:150'],
            'isi_pengaduan' => ['required', 'string'],
            'lokasi' => ['nullable', 'string', 'max:150'],
            'foto_bukti' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_anonim' => ['sometimes', 'boolean'],
        ], [
            'nama_pelapor.required' => 'Nama pelapor wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi agar perangkat desa dapat menghubungi Anda.',
            'kategori.required' => 'Silakan pilih kategori pengaduan.',
            'kategori.in' => 'Kategori pengaduan yang dipilih tidak tersedia.',
            'judul_pengaduan.required' => 'Judul pengaduan wajib diisi.',
            'isi_pengaduan.required' => 'Isi pengaduan wajib diisi.',
            'foto_bukti.image' => 'Berkas bukti harus berupa gambar.',
            'foto_bukti.mimes' => 'Foto bukti harus berformat JPG, JPEG, PNG, atau WEBP.',
            'foto_bukti.max' => 'Ukuran foto bukti maksimal 5 MB.',
        ]);

        $fotoBukti = $request->file('foto_bukti')?->store('pengaduan', 'public');

        if ($request->hasFile('foto_bukti') && $fotoBukti === false) {
            throw new RuntimeException('Foto bukti gagal disimpan.');
        }

        $pengguna = $request->user();
        $wargaId = $pengguna?->isWarga() ? $pengguna->warga_id : null;

        $pengaduan = DB::transaction(function () use ($data, $fotoBukti, $wargaId) {
            $prefix = 'ADU-'.now()->format('Ym');
            $nomorBerikutnya = Pengaduan::query()
                ->where('kode_pengaduan', 'like', $prefix.'-%')
                ->pluck('kode_pengaduan')
                ->map(fn (string $kode) => (int) substr($kode, strlen($prefix) + 1))
                ->max() + 1;

            do {
                $kodeTiket = sprintf('%s-%03d', $prefix, $nomorBerikutnya++);
            } while (Pengaduan::where('kode_pengaduan', $kodeTiket)->exists());

            return Pengaduan::create([
                'kode_pengaduan' => $kodeTiket,
                'warga_id' => $wargaId,
                'nama_pelapor' => $data['nama_pelapor'],
                'no_hp' => $data['no_hp'],
                'kategori' => $data['kategori'],
                'judul_pengaduan' => $data['judul_pengaduan'],
                'isi_pengaduan' => $data['isi_pengaduan'],
                'lokasi' => $data['lokasi'] ?? null,
                'foto_bukti' => $fotoBukti,
                'status' => 'menunggu',
                'is_anonim' => $data['is_anonim'] ?? false,
            ]);
        });

        return redirect()
            ->route('pengaduan.lacak', ['kode' => $pengaduan->kode_pengaduan])
            ->with('sukses', 'Pengaduan berhasil dikirim. Simpan kode tiket untuk melacak statusnya.');
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
