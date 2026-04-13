<?php

namespace App\Http\Controllers;

use App\Models\tr_ca;
use App\Models\tr_ca_transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashAdvanceController extends Controller
{
    public function index()
    {
        // return response()->json(['data' => 'hello']);
        $data = tr_ca::with('trCA')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function showByKode()
    {
        $data = tr_ca::with('trCA')->where('kode_ca', request('kode_ca'))->first();

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
            'user_name' => 'required',
            'judul_kegiatan' => 'required',
            'tahun_anggaran' => 'required',
            'tanggal_mulai' => 'required',
            'tanggal_selesai' => 'required',
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
        $validated['status'] = 'draft';


        $data = tr_ca::create($validated);

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

            // Cari CA berdasarkan kode
            $ca = tr_ca::where('kode_ca', $kode)->firstOrFail();

            // Ambil saldo terakhir
            $lastSaldo = $ca->trCA()->latest()->first();

            $saldoSebelumnya = $lastSaldo ? $lastSaldo->saldo_setelah : 0;

            // Hitung saldo baru
            if ($validated['jenis'] == 'penerimaan') {
                $saldoBaru = $saldoSebelumnya + $validated['jumlah'];
                $ca->total_penerimaan += $validated['jumlah'];
            } else {
                $saldoBaru = $saldoSebelumnya - $validated['jumlah'];
                $ca->total_pengeluaran += $validated['jumlah'];
            }

            // Simpan transaksi lewat relasi
            $transaksi = $ca->trCA()->create([
                'tanggal' => $validated['tanggal'],
                'jenis' => $validated['jenis'],
                'deskripsi' => $validated['deskripsi'],
                'jumlah' => $validated['jumlah'],
                'saldo_setelah' => $saldoBaru,
            ]);

            // Update saldo akhir di header
            $ca->saldo_akhir = $saldoBaru;
            $ca->save();

            return response()->json([
                'success' => true,
                'data' => $transaksi
            ]);
        });
    }


    public function delete_ca($kode)
    {
        $data = tr_ca::where('kode_ca', $kode)->delete();
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }


    public function caPL(Request $request)
    {
        $userId = $request->header('x-api-key');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'x-api-key header tidak ditemukan'
            ], 401);
        }

        $data = tr_ca::with('trCA')
            ->where('user_id', $userId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function postTransaksiCaPl(Request $request, $kode_ca)
    {
        $userId = $request->header('x-api-key');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'x-api-key header tidak ditemukan'
            ], 401);
        }

        // Validasi
        $request->validate([
            'tanggal'   => 'required|date',
            'jenis'     => 'required|in:penerimaan,pengeluaran',
            'deskripsi' => 'nullable|string',
            'jumlah'    => 'required|numeric|min:0'
        ]);

        // Ambil CA
        $ca = tr_ca::where('kode_ca', $kode_ca)
            ->where('user_id', $userId)
            ->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data CA tidak ditemukan'
            ], 404);
        }

        // Hitung saldo baru
        $saldoSebelum = $ca->saldo_akhir;
        $jumlah = $request->jumlah;

        if ($request->jenis == 'penerimaan') {
            $saldoSetelah = $saldoSebelum + $jumlah;
            $ca->total_penerimaan += $jumlah;
        } else {
            $saldoSetelah = $saldoSebelum - $jumlah;
            $ca->total_pengeluaran += $jumlah;
        }

        // Simpan transaksi
        $transaksi = tr_ca_transaction::create([
            'tr_ca_id'       => $ca->id,
            'tanggal'        => $request->tanggal,
            'jenis'          => $request->jenis,
            'deskripsi'      => $request->deskripsi,
            'jumlah'         => $jumlah,
            'saldo_setelah'  => $saldoSetelah,
        ]);

        // Update saldo di CA
        $ca->saldo_akhir = $saldoSetelah;
        $ca->save();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil ditambahkan',
            'data' => $transaksi
        ]);
    }

    public function deleteTransaksiCaPl(Request $request, $id)
    {
        $userId = $request->header('x-api-key');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'x-api-key header tidak ditemukan'
            ], 401);
        }

        // Ambil transaksi
        $transaksi = tr_ca_transaction::find($id);

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }

        // Ambil CA
        $ca = tr_ca::where('id', $transaksi->tr_ca_id)
            ->where('user_id', $userId)
            ->first();

        if (!$ca) {
            return response()->json([
                'success' => false,
                'message' => 'Data CA tidak ditemukan'
            ], 404);
        }

        $jumlah = $transaksi->jumlah;

        // 🔥 Rollback saldo
        if ($transaksi->jenis == 'penerimaan') {
            $ca->total_penerimaan -= $jumlah;
            $ca->saldo_akhir -= $jumlah;
        } else {
            $ca->total_pengeluaran -= $jumlah;
            $ca->saldo_akhir += $jumlah;
        }

        // Hapus transaksi
        $transaksi->delete();

        // Save CA
        $ca->save();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus'
        ]);
    }
}
