<?php

namespace App\Http\Controllers;

use App\Http\Resources\CashAdvanceResource;
use App\Models\tr_ca;
use App\Models\tr_ca_transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Log;

class CashAdvanceController extends Controller
{
    // public function index()
    // {
    //     // return response()->json(['data' => 'hello']);
    //     $data = tr_ca::with('trCA')->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $data
    //     ]);
    // }

    public function index(Request $request)
    {
        $user_id = $request->user_id;

        $data = tr_ca::with('trCA')
            ->where('user_id', $user_id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function showByKode()
    {
        $data = tr_ca::with(['tm_category_ca', 'transaksi'])->where('kode_ca', request('kode_ca'))->first();
        // $data = tr_ca::with(['trCA', 'tm_category_ca'])->where('kode_ca', request('kode_ca'))->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
                'data' => []
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function walletPLshowByKode()
    {
        $data = tr_ca::with(['trCA', 'tm_category_ca'])->where('kode_ca', request('kode_ca'))->first();
        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
                'data' => []
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function showTransaksiByKode($kode_ca)
    {
        $ca = tr_ca::where('kode_ca', $kode_ca)->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $transaksi = tr_ca_transaction::where('tr_ca_id', $ca->id)
            ->orderBy('tanggal', 'desc')
            ->get()
            ->map(function ($item) {
                $item->bukti_url = $item->bukti
                    ? Storage::disk('s3')->url($item->bukti)
                    : null;

                return $item;
            });

        return response()->json([
            'success' => true,
            'data' => $transaksi
        ]);
    }


    public function post_ca(Request $request)
    {
        $validated = $request->validate([
            // 'kode_ca' => 'required|unique:tr_ca,kode_ca',
            'user_id' => 'required',
            'username' => 'required',
            'judul_kegiatan' => 'required',
            'tahun_anggaran' => 'required',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'nullable',
            'total_penerimaan' => 'required',
            'total_pengeluaran' => 'nullable',
            // 'id_ca_category' => 'required',
            'id_ca_category' => 'required|in:1,2',
        ], [
            'user_id.required' => 'User ID harus diisi',
            'username.required' => 'Username harus diisi',
            'judul_kegiatan.required' => 'Judul Kegiatan harus diisi',
            'tahun_anggaran.required' => 'Tahun Anggaran harus diisi',
            'tanggal_mulai.required' => 'Tanggal Mulai harus diisi',
            // 'tanggal_selesai.required' => 'Tanggal Selesai harus diisi',
            'total_penerimaan.required' => 'Total Penerimaan harus diisi',
            // 'total_pengeluaran.required' => 'Total Pengeluaran harus diisi',
            'id_ca_category.required' => 'Kategori CA harus diisi',
            'id_ca_category.in' => 'Kategori CA hanya boleh 1 (Dompet PL) atau 2 (Dompet Kegiatan)',
        ]);

        $tahun = $validated['tahun_anggaran'];

        // Ambil nomor terakhir berdasarkan tahun
        $last = tr_ca::where('tahun_anggaran', $tahun)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            // Ambil angka terakhir dari kode_ca
            $lastNumber = (int) substr($last->kode_ca, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $kode = 'CA-' . $tahun . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $validated['kode_ca'] = $kode;
        $validated['status'] = 'approved';
        $validated['saldo_akhir'] = $validated['total_penerimaan'];


        $data = tr_ca::create($validated);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function delete_ca($kode)
    {
        $data = tr_ca::where('kode_ca', $kode)->delete();
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function postTransaksi(Request $request, $kode)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'deskripsi' => 'required',
            'jumlah' => 'required|numeric|min:1',
        ]);

        return DB::transaction(function () use ($validated, $kode) {

            // Cari data CA berdasarkan kode
            $ca = tr_ca::where('kode_ca', $kode)->firstOrFail();

            // Ambil transaksi terakhir
            $lastSaldo = $ca->trCA()->latest()->first();

            // Saldo awal dari pengajuan
            $saldoAwal = (float) $ca->total_penerimaan;

            // Jika ada transaksi gunakan saldo terakhir
            $saldoSebelumnya = $lastSaldo
                ? (float) $lastSaldo->saldo_setelah
                : $saldoAwal;

            // Hitung saldo
            if ($validated['jenis'] == 'penerimaan') {

                $saldoBaru = $saldoSebelumnya + (float) $validated['jumlah'];

                $ca->total_penerimaan += (float) $validated['jumlah'];

            } else {

                if ((float) $validated['jumlah'] > $saldoSebelumnya) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saldo tidak mencukupi'
                    ], 400);
                }

                $saldoBaru = $saldoSebelumnya - (float) $validated['jumlah'];

                $ca->total_pengeluaran += (float) $validated['jumlah'];
            }

            // Simpan transaksi detail
            $transaksi = $ca->trCA()->create([
                'tanggal' => $validated['tanggal'],
                'jenis' => $validated['jenis'],
                'deskripsi' => $validated['deskripsi'],
                'jumlah' => $validated['jumlah'],
                'saldo_setelah' => $saldoBaru,
            ]);

            // Update saldo akhir di tabel header
            $ca->saldo_akhir = $saldoBaru;
            $ca->save();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'data' => $transaksi,
                'saldo' => [
                    'saldo_sebelumnya' => $saldoSebelumnya,
                    'saldo_setelah' => $saldoBaru,
                ]
            ]);
        });
    }


    // public function delete_ca($kode)
    // {
    //     $data = tr_ca::where('kode_ca', $kode)->delete();
    //     return response()->json([
    //         'success' => true,
    //         'data' => $data
    //     ]);
    // }


    public function caPL(Request $request)
    {
        $userId = $request->user_id;
        $dataCategoryId = $request->data_category_id;
        $tahunAnggaran = $request->tahun_anggaran;
        $status = $request->status;
        $isActive = $request->is_active;


        $query = tr_ca::with([
            'trCA',
            'tm_category_ca'
        ])
            ->where('user_id', $userId);

        // filter category id
        if (!empty($dataCategoryId)) {
            $query->where('id_ca_category', $dataCategoryId);
        }
        if (!empty($tahunAnggaran)) {
            $query->where('tahun_anggaran', $tahunAnggaran);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $isActive);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silahkan cek kembali data nya',
            ], 404);
        }

        return sendResponse(
            'success',
            CashAdvanceResource::collection($data),
            null,
            'Data berhasil diambil'
        );

        // return response()->json([
        //     'success' => true,
        //     'data' => $data
        // ]);
    }
    public function postTransaksiCaPl(Request $request, $kode_ca)
    {
        // Validasi
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'bukti' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'deskripsi' => 'nullable|string',
            'jumlah' => 'required|numeric|min:1'
        ]);

        $bukti = null;
        if ($request->hasFile('bukti')) {
            $bukti = $request->file('bukti')->store('bukti-transaksi', 's3');
        }


        $ca = tr_ca::where('kode_ca', $kode_ca)->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data CA tidak ditemukan'
            ], 404);
        }

        $jumlah = (float) $request->jumlah;

        $saldoSebelum = $ca->saldo_akhir > 0
            ? (float) $ca->saldo_akhir
            : (float) $ca->total_penerimaan;



        if ($request->jenis == 'penerimaan') {

            $saldoSetelah = $saldoSebelum + $jumlah;

            $ca->total_penerimaan += $jumlah;

        } else {

            // cek saldo cukup
            if ($jumlah > $saldoSebelum) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak mencukupi'
                ], 400);
            }

            $saldoSetelah = $saldoSebelum - $jumlah;

            $ca->total_pengeluaran += $jumlah;
        }

        $transaksi = tr_ca_transaction::create([
            'tr_ca_id' => $ca->id,
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'deskripsi' => $request->deskripsi,
            'jumlah' => $jumlah,
            'bukti' => $bukti,
            'saldo_setelah' => $saldoSetelah,
        ]);

        $ca->saldo_akhir = $saldoSetelah;
        $ca->save();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil ditambahkan',
            'data' => $transaksi,
            'saldo' => [
                'saldo_sebelum' => $saldoSebelum,
                'saldo_setelah' => $saldoSetelah,
            ]
        ]);
    }

    public function walletPLPostTransaksi(Request $request, $kode_ca)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'bukti' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'kategori' => 'required',
            'deskripsi' => 'nullable|string',
            'jumlah' => 'required|numeric|min:1'
        ]);

        $bukti = null;
        if ($request->hasFile('bukti')) {
            $bukti = $request->file('bukti')->store('bukti-transaksi', 's3');
        }


        $ca = tr_ca::where('kode_ca', $kode_ca)->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data CA tidak ditemukan'
            ], 404);
        }

        $jumlah = (float) $request->jumlah;

        $saldoSebelum = $ca->saldo_akhir > 0
            ? (float) $ca->saldo_akhir
            : (float) $ca->total_penerimaan;



        if ($request->jenis == 'penerimaan') {

            $saldoSetelah = $saldoSebelum + $jumlah;

            $ca->total_penerimaan += $jumlah;

        } else {

            // cek saldo cukup
            if ($jumlah > $saldoSebelum) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak mencukupi'
                ], 400);
            }

            $saldoSetelah = $saldoSebelum - $jumlah;

            $ca->total_pengeluaran += $jumlah;
        }

        $transaksi = tr_ca_transaction::create([
            'tr_ca_id' => $ca->id,
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'deskripsi' => $request->deskripsi,
            'jumlah' => $jumlah,
            'bukti' => $bukti,
            'saldo_setelah' => $saldoSetelah,
        ]);

        $ca->saldo_akhir = $saldoSetelah;
        $ca->save();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil ditambahkan',
            'data' => $transaksi,
            'saldo' => [
                'saldo_sebelum' => $saldoSebelum,
                'saldo_setelah' => $saldoSetelah,
            ]
        ]);
    }

    public function deleteTransaksiCaPl($id)
    {
        // Cari transaksi
        $transaksi = tr_ca_transaction::find($id);

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        // Ambil data CA
        $ca = tr_ca::find($transaksi->tr_ca_id);

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data CA tidak ditemukan'
            ], 404);
        }

        $jumlah = (float) $transaksi->jumlah;

        /*
        |--------------------------------------------------------------------------
        | Kembalikan saldo berdasarkan jenis transaksi
        |--------------------------------------------------------------------------
        */

        if ($transaksi->jenis == 'penerimaan') {

            // rollback saldo penerimaan
            $ca->saldo_akhir -= $jumlah;

            // rollback total penerimaan
            $ca->total_penerimaan -= $jumlah;

        } else {

            // rollback saldo pengeluaran
            $ca->saldo_akhir += $jumlah;

            // rollback total pengeluaran
            $ca->total_pengeluaran -= $jumlah;
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus file bukti jika ada
        |--------------------------------------------------------------------------
        */

        if ($transaksi->bukti && Storage::disk('public')->exists($transaksi->bukti)) {
            Storage::disk('public')->delete($transaksi->bukti);
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus transaksi
        |--------------------------------------------------------------------------
        */

        $transaksi->delete();

        /*
        |--------------------------------------------------------------------------
        | Simpan perubahan saldo
        |--------------------------------------------------------------------------
        */

        $ca->save();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus'
        ]);
    }

    public function close_ca($kode_ca)
    {
        $ca = tr_ca::where('kode_ca', $kode_ca)->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data Cash Advance tidak ditemukan'
            ], 404);
        }

        $ca->update([
            'is_active' => 0,
            'status' => 'closing',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cash Advance berhasil ditutup',
            'data' => $ca->fresh()
        ]);
    }

    public function topupWalletKegiatan(Request $request)
    {
        $validated = $request->validate([
            // 'kode_ca' => 'required|unique:tr_ca,kode_ca',
            'user_id' => 'required',
            'username' => 'required',
            'tahun_anggaran' => 'required',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'nullable',
            'total_pengeluaran' => 'nullable',
            'total_penerimaan' => 'required',
        ], [
            'user_id.required' => 'User ID harus diisi',
            'username.required' => 'Username harus diisi',
            'tahun_anggaran.required' => 'Tahun Anggaran harus diisi',
            'tanggal_mulai.required' => 'Tanggal Mulai harus diisi',
            'total_penerimaan.required' => 'Total Penerimaan harus diisi',
        ]);

        $tahun = $validated['tahun_anggaran'];
        // $validated['total_penerimaan'] = 2000000;
        $validated['id_ca_category'] = 1;

        // Ambil nomor terakhir berdasarkan tahun
        $last = tr_ca::where('tahun_anggaran', $tahun)->orderBy('id', 'desc')->first();

        if ($last) {
            $nomor = $last->id + 1;
        } else {
            $nomor = 1;
        }

        $kode = 'CA' . $tahun . str_pad($nomor, 4, '0', STR_PAD_LEFT);
        $validated['kode_ca'] = $kode;
        $validated['kode_ca'] = $kode;
        $validated['judul_kegiatan'] = 'Dompet Kegiatan';
        $validated['status'] = 'approved';
        $validated['saldo_akhir'] = $validated['total_penerimaan'];

        $ca = tr_ca::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cash Advance berhasil dibuat',
            'data' => $ca
        ]);
    }

    private function createTopupWalletPL(array $validated)
    {
        $tahun = $validated['tahun_anggaran'];

        $validated['total_penerimaan'] = 2000000;
        $validated['id_ca_category'] = 1;

        $last = tr_ca::where('tahun_anggaran', $tahun)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $last
            ? ((int) substr($last->kode_ca, -3)) + 1
            : 1;

        $validated['kode_ca'] = 'CA-' . $tahun . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $validated['judul_kegiatan'] = 'Dompet PL';
        $validated['status'] = 'approved';
        $validated['saldo_akhir'] = $validated['total_penerimaan'];

        return tr_ca::create($validated);
    }

    public function topupWalletPL(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'username' => 'required',
            'tahun_anggaran' => 'required',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'nullable',
            'total_pengeluaran' => 'nullable',
        ]);

        $data = $this->createTopupWalletPL($validated);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Topup wallet PL berhasil'
        ]);
    }

    public function esekusiTopupWalletPL(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'username' => 'required',
            'tahun_anggaran' => 'required',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'nullable',
            'total_pengeluaran' => 'nullable',
        ]);

        DB::beginTransaction();

        try {

            // Cari wallet aktif user
            $wallet = tr_ca::where('user_id', $validated['user_id'])
                ->where('id_ca_category', 1)
                ->where('status', 'approved')
                ->latest('id')
                ->first();

            // Kalau ada, tutup wallet lama
            if ($wallet) {
                $wallet->update([
                    'status' => 'closing',
                    'is_active' => 0,
                    // 'tanggal_selesai' => now(),
                ]);
            }

            // Buat wallet baru
            $newWallet = $this->createTopupWalletPL($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $wallet
                    ? 'Wallet lama berhasil ditutup dan wallet baru dibuat.'
                    : 'Wallet pertama berhasil dibuat.',
                'data' => $newWallet
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function walletPL(Request $request)
    {
        $userId = $request->user_id;
        $dataCategoryId = $request->data_category_id;
        $tahunAnggaran = $request->tahun_anggaran;
        $status = $request->status;
        $isActive = $request->is_active;


        $query = tr_ca::with([
            'tr_ca_transaction',
            'tm_category_ca'
        ])
            ->where('id_ca_category', 1)
            ->where('is_active', 1)
            ->where('user_id', $userId);

        // filter category id
        if (!empty($dataCategoryId)) {
            $query->where('id_ca_category', $dataCategoryId);
        }
        if (!empty($tahunAnggaran)) {
            $query->where('tahun_anggaran', $tahunAnggaran);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $isActive);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silahkan cek kembali data nya',
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
    public function walletPLRiwayat(Request $request)
    {
        $userId = $request->user_id;
        $dataCategoryId = $request->data_category_id;
        $tahunAnggaran = $request->tahun_anggaran;
        $status = $request->status;
        $isActive = $request->is_active;


        $query = tr_ca::with([
            'trCA',
            'tm_category_ca'
        ])
            ->where('id_ca_category', 1)
            // ->where('is_active', 1)
            ->where('user_id', $userId);

        // filter category id
        if (!empty($dataCategoryId)) {
            $query->where('id_ca_category', $dataCategoryId);
        }
        if (!empty($tahunAnggaran)) {
            $query->where('tahun_anggaran', $tahunAnggaran);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $isActive);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silahkan cek kembali data nya',
            ], 404);
        }

        return sendResponse(
            'success',
            CashAdvanceResource::collection($data),
            null,
            'Data berhasil diambil'
        );
    }
}
