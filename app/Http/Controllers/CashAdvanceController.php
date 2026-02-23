<?php

namespace App\Http\Controllers;

use App\Models\tr_ca;
use Illuminate\Http\Request;

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
            'kode_ca' => 'required|unique:tr_ca,kode_ca',
            'judul_kegiatan' => 'required',
            'tahun_anggaran' => 'required',
        ]);

        $data = tr_ca::create($validated);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
