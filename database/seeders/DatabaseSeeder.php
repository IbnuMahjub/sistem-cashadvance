<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\tr_ca;
use App\Models\tr_ca_transaction;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $ca = tr_ca::create([
            'kode_ca' => 'CA-2025-001',
            'judul_kegiatan' => 'Pengadaan Intelijen IMSI Catcher',
            'tahun_anggaran' => 2025,
            'tanggal_mulai' => '2025-10-29',
            'tanggal_selesai' => '2026-01-21',
            'total_penerimaan' => 2000000,
            'total_pengeluaran' => 2380830,
            'saldo_akhir' => -380830,
            'status' => 'draft'
        ]);

        // Saldo awal
        $saldo = 0;

        // 1️⃣ PENERIMAAN
        $saldo += 2000000;

        tr_ca_transaction::create([
            'tr_ca_id' => $ca->id,
            'tanggal' => '2025-10-29',
            'jenis' => 'penerimaan',
            'deskripsi' => 'Uang CA PL',
            'jumlah' => 2000000,
            'saldo_setelah' => $saldo,
        ]);

        // 2️⃣ PENGELUARAN
        $saldo -= 58830;

        tr_ca_transaction::create([
            'tr_ca_id' => $ca->id,
            'tanggal' => '2025-11-19',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Konsumsi kopi bersama PPK',
            'jumlah' => 58830,
            'saldo_setelah' => $saldo,
        ]);

        // 3️⃣ PENGELUARAN
        $saldo -= 590000;

        tr_ca_transaction::create([
            'tr_ca_id' => $ca->id,
            'tanggal' => '2026-01-20',
            'jenis' => 'pengeluaran',
            'deskripsi' => 'Tiket Bandung 2 Orang',
            'jumlah' => 590000,
            'saldo_setelah' => $saldo,
        ]);
    }
}
