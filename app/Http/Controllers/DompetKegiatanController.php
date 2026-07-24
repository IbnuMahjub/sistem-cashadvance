<?php

namespace App\Http\Controllers;

use App\Http\Resources\CashAdvanceResource;
use App\Models\tr_ca;
use App\Models\tr_ca_transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DompetKegiatanController extends Controller
{
    private function recalculateSaldo($ca)
    {
        $transaksi = tr_ca_transaction::where('tr_ca_id', $ca->id)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $saldo = $ca->saldo_awal_priode;

        $totalPenerimaan = 0;
        $totalPengeluaran = 0;

        foreach ($transaksi as $item) {

            if ($item->jenis == 'penerimaan') {

                $saldo += $item->jumlah;
                $totalPenerimaan += $item->jumlah;

            } else {

                if ($saldo < $item->jumlah) {
                    return false;
                }

                $saldo -= $item->jumlah;
                $totalPengeluaran += $item->jumlah;
            }

            $item->saldo_setelah = $saldo;
            $item->save();
        }

        $ca->update([
            'total_penerimaan' => $totalPenerimaan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo_akhir' => $saldo,
        ]);

        return true;
    }
    public function walletPLRiwayatKegiatan(Request $request)
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


    public function topupWalletKegiatan(Request $request)
    {
        $validated = $request->validate([
            // 'kode_ca' => 'required|unique:tr_ca,kode_ca',
            'judul_kegiatan' => 'required',
            'user_id' => 'required',
            'username' => 'required',
            'tahun_anggaran' => 'required',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'nullable',
            'total_pengeluaran' => 'nullable',
            'total_penerimaan' => 'required',
            'bukti' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'judul_kegiatan.required' => 'Judul Kegiatan harus diisi',
            'user_id.required' => 'User ID harus diisi',
            'username.required' => 'Username harus diisi',
            'tahun_anggaran.required' => 'Tahun Anggaran harus diisi',
            'tanggal_mulai.required' => 'Tanggal Mulai harus diisi',
            'total_penerimaan.required' => 'Total Penerimaan harus diisi',
        ]);

        $bukti = null;
        if ($request->hasFile('bukti')) {
            $bukti = $request->file('bukti')->store('bukti-pencairan', 's3');
        }
        $tahun = $validated['tahun_anggaran'];
        // $validated['total_penerimaan'] = 2000000;
        $validated['id_ca_category'] = 2;

        // Ambil nomor terakhir berdasarkan tahun
        $last = tr_ca::where('tahun_anggaran', $tahun)->orderBy('id', 'desc')->first();

        if ($last) {
            $nomor = $last->id + 1;
        } else {
            $nomor = 1;
        }

        $kode = 'CA-' . $tahun . '-' . str_pad($nomor, 4, '0', STR_PAD_LEFT);
        // $kode = 'CA' . $tahun . str_pad($nomor, 4, '0', STR_PAD_LEFT);
        $validated['kode_ca'] = $kode;
        $validated['kode_ca'] = $kode;
        // $validated['judul_kegiatan'] = 'Dompet Kegiatan';
        $validated['status'] = 'approved';
        $validated['saldo_akhir'] = $validated['total_penerimaan'];
        // $validated['is_active'] = 1;
        $validated['bukti'] = $bukti;

        $validated['saldo_awal_priode'] = $validated['total_penerimaan'];

        $ca = tr_ca::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Dompet Kegiatan berhasil dibuat',
            'data' => $ca
        ]);
    }

    public function postTransaksiKegiatan(Request $request, $kode_ca)
    {
        $request->validate([
            'kategori' => 'required',
            'tanggal' => 'required|date',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'bukti' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
            'deskripsi' => 'nullable|string',
            'jumlah' => 'required|numeric|min:1',
        ]);

        $ca = tr_ca::where('kode_ca', $kode_ca)->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data CA tidak ditemukan'
            ], 404);
        }

        // $bukti = null;

        // if ($request->hasFile('bukti')) {
        //     $bukti = $request->file('bukti')->store('bukti-transaksi-kegiatan', 's3');
        // }
        $bukti = null;

        if ($request->hasFile('bukti')) {
            // $bukti = $request->file('bukti')->store('bukti-transaksi-capl', 's3');
            $tahun = date('Y'); // atau Carbon::parse($request->tanggal)->year

            $folder = "bukti-transaksi-kegiatan/{$tahun}/{$ca->kode_ca}-{$ca->user_id}";

            $bukti = $request->file('bukti')->store($folder, 's3');
        }

        $transaksi = tr_ca_transaction::create([
            'tr_ca_id' => $ca->id,
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'deskripsi' => $request->deskripsi,
            'jumlah' => $request->jumlah,
            'kategori' => $request->kategori,
            'bukti' => $bukti,
            'saldo_setelah' => 0,
        ]);

        // Hitung ulang semua saldo
        if (!$this->recalculateSaldo($ca)) {

            $transaksi->delete();

            return response()->json([
                'success' => false,
                'message' => 'Saldo tidak mencukupi.'
            ], 400);
        }

        $transaksi->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil ditambahkan',
            'data' => $transaksi
        ]);
    }

    public function closeKegiatan(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:tr_ca,id',
            'bukti_setor' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        DB::beginTransaction();

        try {

            $ca = tr_ca::findOrFail($validated['id']);

            $buktiSetor = $ca->bukti_setor;

            // if ($request->hasFile('bukti_setor')) {
            //     $buktiSetor = $request->file('bukti_setor')
            //         ->store('bukti-setor', 's3');
            // }
            if ($request->hasFile('bukti_setor')) {

                // hapus file lama jika ada
                if ($ca->bukti_setor && Storage::disk('s3')->exists($ca->bukti_setor)) {
                    Storage::disk('s3')->delete($ca->bukti_setor);
                }

                $tahun = date('Y'); // atau Carbon::parse($ca->tanggal_mulai)->year

                $folder = "bukti-transaksi-kegiatan/{$tahun}/{$ca->kode_ca}-{$ca->user_id}/setorbukti";

                $buktiSetor = $request->file('bukti_setor')->store($folder, 's3');
            }

            $ca->update([
                'status' => 'closing',
                'is_active' => 0,
                'bukti_setor' => $buktiSetor,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cash Advance berhasil ditutup.',
                'data' => $ca->fresh(),
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateTransaksiKegiatan(Request $request, $kode_ca, $id)
    {
        $request->validate([
            'kategori' => 'required',
            'tanggal' => 'required|date',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'deskripsi' => 'nullable|string',
            'jumlah' => 'required|numeric|min:1',
            'bukti' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
        ]);

        DB::beginTransaction();

        try {

            $ca = tr_ca::where('kode_ca', $kode_ca)->first();

            if (!$ca) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data CA tidak ditemukan'
                ], 404);
            }

            $transaksi = tr_ca_transaction::where('tr_ca_id', $ca->id)
                ->find($id);

            if (!$transaksi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan'
                ], 404);
            }

            // upload bukti baru
            if ($request->hasFile('bukti')) {

                if ($transaksi->bukti) {
                    Storage::disk('s3')->delete($transaksi->bukti);
                }

                $tahun = date('Y'); // atau Carbon::parse($request->tanggal)->year

                $folder = "bukti-transaksi-kegiatan/{$tahun}/{$ca->kode_ca}-{$ca->user_id}";

                $transaksi->bukti = $request->file('bukti')->store($folder, 's3');
            }

            $transaksi->tanggal = $request->tanggal;
            $transaksi->kategori = $request->kategori;
            $transaksi->jenis = $request->jenis;
            $transaksi->deskripsi = $request->deskripsi;
            $transaksi->jumlah = $request->jumlah;
            $transaksi->save();

            // ============================
            // Hitung ulang semua transaksi
            // ============================

            $saldo = $ca->saldo_awal_priode;

            $totalPenerimaan = 0;
            $totalPengeluaran = 0;

            $listTransaksi = tr_ca_transaction::where('tr_ca_id', $ca->id)
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get();

            foreach ($listTransaksi as $trx) {

                if ($trx->jenis == 'penerimaan') {
                    $saldo += $trx->jumlah;
                    $totalPenerimaan += $trx->jumlah;
                } else {
                    $saldo -= $trx->jumlah;
                    $totalPengeluaran += $trx->jumlah;
                }

                $trx->saldo_setelah = $saldo;
                $trx->save();
            }

            $ca->update([
                'total_penerimaan' => $totalPenerimaan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo_akhir' => $saldo,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diupdate',
                'data' => $transaksi->fresh()
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteTransaksiKegiatan($kode_ca, $id)
    {
        $ca = tr_ca::where('kode_ca', $kode_ca)->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data CA tidak ditemukan'
            ], 404);
        }

        $transaksi = tr_ca_transaction::where('tr_ca_id', $ca->id)
            ->find($id);

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        // if ($transaksi->bukti) {
        //     Storage::disk('s3')->delete($transaksi->bukti);
        // }

        if ($transaksi->bukti && Storage::disk('s3')->exists($transaksi->bukti)) {
            Storage::disk('s3')->delete($transaksi->bukti);
        }

        $transaksi->delete();

        $this->recalculateSaldo($ca);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi kegiatanberhasil dihapus'
        ]);
    }

    public function showTransaksiKegiatanByKode(Request $request, $kode_ca)
    {
        $ca = tr_ca::where('kode_ca', $kode_ca)->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = tr_ca_transaction::where('tr_ca_id', $ca->id);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('deskripsi', 'like', "%{$search}%")
                    ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        $transaksi = $query
            ->orderBy('tanggal', 'desc')
            ->paginate($perPage);

        $transaksi->getCollection()->transform(function ($item) use ($ca) {

            return [
                'dompet_id' => $item->tr_ca_id,
                'kode_ca' => $ca->kode_ca,
                'id_transaksi' => $item->id,
                'tanggal' => $item->tanggal,
                'jenis' => $item->jenis,
                'deskripsi' => $item->deskripsi,
                'kategori' => $item->kategori,
                'jumlah' => (float) $item->jumlah,
                'saldo_setelah' => (float) $item->saldo_setelah,
                'bukti' => $item->bukti,
                'bukti_url' => $item->bukti
                    ? Storage::disk('s3')->url($item->bukti)
                    : null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transaksi->items(),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page' => $transaksi->lastPage(),
                'per_page' => $transaksi->perPage(),
                'total' => $transaksi->total(),
                'from' => $transaksi->firstItem(),
                'to' => $transaksi->lastItem(),
            ]
        ]);
    }

    public function listWalletKegiatan(Request $request)
    {
        $userId = $request->user_id;
        $dataCategoryId = $request->data_category_id;
        $tahunAnggaran = $request->tahun_anggaran;
        $status = $request->status;
        $isActive = $request->is_active;
        $search = $request->search;
        // $perPage = $request->input('per_page', 10); 

        $query = tr_ca::query()
            ->with('tm_category_ca')
            ->where('id_ca_category', 2)
            ->where('user_id', $userId);

        if (!empty($search)) {
            $query->whereHas('tr_ca_transaction', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('deskripsi', 'like', "%{$search}%")
                        ->orWhere('kategori', 'like', "%{$search}%");
                });
            });
        }

        if ($request->boolean('tr_ca_transaction')) {
            $query->with([
                'tr_ca_transaction' => function ($q) use ($search) {
                    if (!empty($search)) {
                        $q->where(function ($query) use ($search) {
                            $query->where('deskripsi', 'like', "%{$search}%")
                                ->orWhere('kategori', 'like', "%{$search}%");
                        });
                    }
                }
            ]);
        }

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

        $perPage = $request->input('per_page', 10);

        $paginator = $query
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return sendResponse(
            'success',
            $paginator->items(),
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                // 'has_next_page' => $paginator->hasMorePages(),
            ],
            'Data berhasil diambil'
        );
    }

    public function showWalletKegiatanByKode(Request $request, $kode_ca)
    {
        $ca = tr_ca::where('kode_ca', $kode_ca)->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ca
        ]);
    }
}
