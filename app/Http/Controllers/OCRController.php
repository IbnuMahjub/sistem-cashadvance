<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Thiagoalessio\TesseractOCR\TesseractOCR;

class OCRController extends Controller
{
    public function scan(Request $request)
    {
        try {

            $request->validate([
                'bukti' => 'required|image'
            ]);

            // SIMPAN FILE
            $path = $request->file('bukti')->store('temp', 'public');

            // FULL PATH
            $fullPath = storage_path('app/public/' . $path);

            // DEBUG FILE
            if (!file_exists($fullPath)) {

                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan',
                    'path' => $fullPath
                ]);
            }

            // OCR
            $text = (new TesseractOCR($fullPath))
                ->lang('eng')
                ->run();

            preg_match('/\d{2}[\/\-]\d{2}[\/\-]\d{4}/', $text, $tgl);

            $tanggal = now()->format('Y-m-d');

            if(isset($tgl[0])){

                try {

                    $tanggal = str_replace('/', '-', $tgl[0]);

                    $tanggal = date('Y-m-d', strtotime($tanggal));

                } catch (\Exception $e) {

                    $tanggal = now()->format('Y-m-d');

                }

            }

            // AMBIL TOTAL
            preg_match('/TOTAL\s*[: ]?\s*([\d\.,]+)/i', $text, $jm);

            $jumlah = 0;

            if(isset($jm[1])){

                $jumlah = preg_replace('/[^0-9]/', '', $jm[1]);
            }

            // DESKRIPSI
            $lines = explode("\n", trim($text));

            $deskripsi = $lines[0] ?? 'Transaksi';

            return response()->json([
                'success' => true,
                'ocr_text' => $text,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah,
                'deskripsi' => $deskripsi,
                'jenis' => 'pengeluaran',
                'path' => $fullPath
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);

        }
    }
}