<?php

namespace App\Http\Controllers;

use App\Http\Resources\CashAdvanceResource;
use App\Models\tr_ca;
use App\Models\tr_ca_transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DompetPLController extends Controller
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
        $validated['saldo_awal_priode'] = $validated['total_penerimaan'];

        return tr_ca::create($validated);
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

    public function showTransaksiByKode(Request $request, $kode_ca)
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

    public function postTransaksiCaPl(Request $request, $kode_ca)
    {
        $request->validate([
            'kategori' => 'required',
            'tanggal' => 'required|date',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'bukti' => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf|max:2048',
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

        $bukti = null;

        if ($request->hasFile('bukti')) {
            // $bukti = $request->file('bukti')->store('bukti-transaksi-capl', 's3');
            $tahun = date('Y'); // atau Carbon::parse($request->tanggal)->year

            $folder = "bukti-transaksi-capl/{$tahun}/{$ca->kode_ca}-{$ca->user_id}";

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

    public function updateTransaksiCaPl(Request $request, $kode_ca, $id)
    {
        $request->validate([
            'kategori' => 'required',
            'tanggal' => 'required|date',
            'jenis' => 'required|in:penerimaan,pengeluaran',
            'deskripsi' => 'nullable|string',
            'jumlah' => 'required|numeric|min:1',
            'bukti' => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf|max:2048',
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

            // // upload bukti baru
            // if ($request->hasFile('bukti')) {

            //     if ($transaksi->bukti) {
            //         Storage::disk('s3')->delete($transaksi->bukti);
            //     }

            //     $transaksi->bukti = $request->file('bukti')
            //         ->store('bukti-transaksi-capl', 's3');
            // }

            // upload bukti baru
            if ($request->hasFile('bukti')) {

                if ($transaksi->bukti) {
                    Storage::disk('s3')->delete($transaksi->bukti);
                }

                $tahun = date('Y'); // atau Carbon::parse($request->tanggal)->year

                $folder = "bukti-transaksi-capl/{$tahun}/{$ca->kode_ca}-{$ca->user_id}";

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

    public function deleteTransaksiCaPl($kode_ca, $id)
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
            'message' => 'Transaksi berhasil dihapus'
        ]);
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

    public function riwayatWalletPL(Request $request)
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

        // return sendResponse(
        //     'success',
        //     CashAdvanceResource::collection($data),
        //     null,
        //     'Data berhasil diambil'
        // );

        return response()->json([
            'success' => true,
            'data' => $data->map(function ($item) {
                return [
                    'id_ca' => $item->id,
                    'kode_ca' => $item->kode_ca,
                    'data_category_id' => $item->id_ca_category,
                    'nama_category' => $item->tm_category_ca->name_category,
                    'status' => $item->status,
                    'total_pengeluaran' => $item->total_pengeluaran,
                    'saldo_akhir' => $item->saldo_akhir,
                    'laporan' => env('APP_URL') . '/api/laporan-wallet-pl/' . $item->id
                ];
            })
        ]);
    }

    public function laporanWalletPL($id)
    {
        $ca = tr_ca::with([
            'tr_ca_transaction',
            'tm_category_ca'
        ])->findOrFail($id);


        $pdf = Pdf::loadView('pdf.laporan-wallet-pl', [
            'ca' => $ca
        ]);
        // dd($ca);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('laporan-wallet-pl-' . $ca->kode_ca . '.pdf');

        // kalau ingin preview di browser
        // return $pdf->stream('laporan-wallet-pl.pdf');
    }

    public function listWalletPL(Request $request)
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
            ->where('id_ca_category', 1)
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

    public function showWalletPlByKode(Request $request, $kode_ca)
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
