<?php

namespace App\Http\Controllers;

use App\Http\Resources\CashAdvanceResource;
use App\Models\tr_ca;
use App\Models\tr_ca_transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Log;

class CashAdvanceController extends Controller
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





    // delete transaksi ca pl




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
            'tr_ca_transaction',
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
    // use Carbon\Carbon;

    public function cashadvance(Request $request)
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
        } else {
            $query->where('is_active', 1);
        }

        // Query untuk Card Right

        $queryRight = tr_ca::with([
            'tr_ca_transaction',
            'tm_category_ca'
        ])
            ->where('id_ca_category', 2)
            ->where('user_id', $userId);
        // $queryRight = tr_ca::where('id_ca_category', 2)
        //     ->where('user_id', $userId);

        if (!empty($tahunAnggaran)) {
            $queryRight->where('tahun_anggaran', $tahunAnggaran);
        }

        if (!empty($status)) {
            $queryRight->where('status', $status);
        }

        if ($request->has('is_active')) {
            $queryRight->where('is_active', $isActive);
        } else {
            $queryRight->where('is_active', 1);
        }



        $dataRight = $queryRight->get();

        $data = $query->get();

        $totalExpense = $data->sum('total_pengeluaran');

        $kategoriPengeluaran = $data
            ->flatMap(function ($ca) {
                return $ca->tr_ca_transaction;
            })
            ->where('jenis', 'pengeluaran')
            ->groupBy('kategori')
            ->map(function ($transactions, $kategori) use ($totalExpense) {

                $jumlah = $transactions->sum('jumlah');

                return [
                    'nama' => $kategori,
                    'jumlah' => (float) $jumlah,
                    'persentase' => $totalExpense > 0
                        ? round(($jumlah / $totalExpense) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('jumlah')
            ->values();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silahkan cek kembali datanya',
            ], 404);
        }

        // Label bulan
        $categories = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec'
        ];

        // Default semua bulan = 0
        $income = array_fill(0, 12, 0);
        $expense = array_fill(0, 12, 0);

        foreach ($data as $item) {

            $month = Carbon::parse($item->tanggal_mulai)->month;

            $income[$month - 1] += (float) $item->total_penerimaan;
            $expense[$month - 1] += (float) $item->total_pengeluaran;
        }

        return response()->json([
            'status' => 'success',

            'card_left' => [
                'id_ca_wallet_pl' => $data->first()->id,
                'kode_ca' => $data->first()->kode_ca,
                'sisa_saldo' => (float) $data->sum('saldo_akhir'),
                'income' => (float) $data->sum('saldo_awal_priode'),
                'expense' => (float) $data->sum('total_pengeluaran'),
            ],
            'card_right' => [
                'jumlah_dompet' => $dataRight->count(),
                'data' => $dataRight->map(function ($item) {
                    return [
                        'id_ca_kegiatan' => $item->id,
                        'kode_ca' => $item->kode_ca,
                        'judul_kegiatan' => $item->judul_kegiatan,
                        'sisa_saldo' => (float) $item->saldo_akhir,
                        // 'income' => (float) $item->total_penerimaan,
                        // 'expense' => (float) $item->total_pengeluaran,
                    ];
                }),
                'total_pengeluaran' => (float) $totalExpense,

                'kategori_pengeluaran' => $kategoriPengeluaran,

            ],

            'chart' => [
                'categories' => $categories,
                'series' => [
                    [
                        'name' => 'Income',
                        'data' => $income,
                    ],
                    [
                        'name' => 'Expense',
                        'data' => $expense,
                    ]
                ]
            ],

            // 'data' => $data
        ]);
    }

    public function allRiwayatWallet(Request $request)
    {
        $userId = $request->user_id;
        $dataCategoryId = $request->data_category_id;
        $tahunAnggaran = $request->tahun_anggaran;
        $status = $request->status;
        $isActive = $request->is_active;
        $search = $request->search;


        $query = tr_ca::with([
            'tr_ca_transaction',
            'tm_category_ca'
        ])
            ->where('user_id', $userId);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('judul_kegiatan', 'like', "%{$search}%")
                    ->orWhere('kode_ca', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

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

        $perPage = $request->input('per_page', 10);

        $data = $query
            ->orderByDesc('created_at')
            ->paginate($perPage);

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silahkan cek kembali data nya',
            ], 404);
        }

        return sendResponse(
            'success',
            CashAdvanceResource::collection($data->items()),
            [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
            'Data berhasil diambil'
        );

        // return response()->json([
        //     'success' => true,
        //     'data' => $data
        // ]);
    }

}
