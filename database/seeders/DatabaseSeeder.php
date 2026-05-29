<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\tm_category_ca;
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

        $ca_category = tm_category_ca::create([
            'id' => 1,
            'name_category' => 'Dompet PL'
        ]);

        $ca_category = tm_category_ca::create([
            'id' => 2,
            'name_category' => 'Dompet Kegiatan'
        ]);

        // $ca = tr_ca::create([
        //     'id_ca_category' => 1,
        //     'kode_ca' => 'CAPL-2025-001',
        //     'user_id' => 1,
        //     'username' => 'birly',
        //     'judul_kegiatan' => 'Pengadaan Intelijen IMSI Catcher',
        //     'tahun_anggaran' => 2025,
        //     'tanggal_mulai' => '2025-10-29',
        //     'tanggal_selesai' => '2026-01-21',
        //     'total_penerimaan' => 2000000,
        //     'total_pengeluaran' => 2380830,
        //     'saldo_akhir' => -380830,
        //     'status' => 'approved'
        // ]);
        // $ca = tr_ca::create([
        //     'id_ca_category' => 1,
        //     'kode_ca' => 'CAPL-2024-001',
        //     'user_id' => 1,
        //     'username' => 'birly',
        //     'judul_kegiatan' => 'Pengadaan Intelijen IMSI Catcher 2024',
        //     'tahun_anggaran' => 2024,
        //     'tanggal_mulai' => '2024-10-29',
        //     'tanggal_selesai' => '2024-11-21',
        //     'total_penerimaan' => 2000000,
        //     'total_pengeluaran' => 2380830,
        //     'saldo_akhir' => -380830,
        //     'status' => 'approved'
        // ]);

        // // Saldo awal
        // $saldo = 0;

        // // 1️⃣ PENERIMAAN
        // $saldo += 2000000;

        // tr_ca_transaction::create([
        //     'tr_ca_id' => $ca->id,
        //     'tanggal' => '2025-10-29',
        //     'jenis' => 'penerimaan',
        //     'deskripsi' => 'Uang CA PL',
        //     'jumlah' => 2000000,
        //     'saldo_setelah' => $saldo,
        // ]);

        // // 2️⃣ PENGELUARAN
        // $saldo -= 58830;

        // tr_ca_transaction::create([
        //     'tr_ca_id' => $ca->id,
        //     'tanggal' => '2025-11-19',
        //     'jenis' => 'pengeluaran',
        //     'deskripsi' => 'Konsumsi kopi bersama PPK',
        //     'jumlah' => 58830,
        //     'saldo_setelah' => $saldo,
        // ]);



        // $ca = tr_ca::create([
        //     'id_ca_category' => 2,
        //     'kode_ca' => 'CAKEGIATAN-2025-002',
        //     'user_id' => 1,
        //     'username' => 'birly',
        //     'judul_kegiatan' => 'Kunjungan Ke Gudang Barang Milik Negara (BMN) dan Pengadaan Intelijen IMSI Catcher',
        //     'tahun_anggaran' => 2025,
        //     'tanggal_mulai' => '2025-10-29',
        //     'tanggal_selesai' => '2026-01-21',
        //     'total_penerimaan' => 4000000,
        //     'total_pengeluaran' => 3500000,
        //     'saldo_akhir' => -500000,
        //     'status' => 'draft'
        // ]);

        // $ca = tr_ca::create([
        //     'id_ca_category' => 2,
        //     'kode_ca' => 'CAPELATIHAN-2025-002',
        //     'user_id' => 1,
        //     'username' => 'birly',
        //     'judul_kegiatan' => 'Kegiatan Pelatihan Intelijen IMSI Catcher',
        //     'tahun_anggaran' => 2025,
        //     'tanggal_mulai' => '2025-10-29',
        //     'tanggal_selesai' => '2026-01-21',
        //     'total_penerimaan' => 4000000,
        //     'total_pengeluaran' => 3500000,
        //     'saldo_akhir' => -500000,
        //     'status' => 'draft'
        // ]);

    }
}
