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


        // $data_ca = tr_ca::create([
        //     'id' => 1,
        //     'id_ca_category' => 1,
        //     'kode_ca' => 'CA-2026-001',
        //     'user_id' => 3,
        //     'username' => 'ibnu mahjub',
        //     'judul_kegiatan' => 'Dompet PL',
        //     'tahun_anggaran' => 2026,
        //     'tanggal_mulai' => '2026-06-26',
        //     'total_penerimaan' => 2000000,
        //     'status' => 'approved',
        //     'is_active' => 1
        // ]);


        // $data_ca_transaction = tr_ca_transaction::create([
        //     'id' => 1,
        //     'tr_ca_id' => 1,
        //     'tanggal' => '2026-06-05',
        //     'jenis' => 'pengeluaran',
        //     'deskripsi' => 'Pulsa & paket data',
        //     'bukti' => 'testing.png',
        //     'kategori' => 'Komunikasi',
        //     'jumlah' => 145000,
        //     'saldo_setelah' => 1350000
        // ]);
        // $data_ca_transaction = tr_ca_transaction::create([
        //     'id' => 2,
        //     'tr_ca_id' => 1,
        //     'tanggal' => '2026-06-04',
        //     'jenis' => 'pengeluaran',
        //     'deskripsi' => 'Pembelian alat tulis',
        //     'bukti' => 'testing2.png',
        //     'kategori' => 'ATK',
        //     'jumlah' => 75000,
        //     'saldo_setelah' => 1495000
        // ]);
        // $data_ca_transaction = tr_ca_transaction::create([
        //     'id' => 3,
        //     'tr_ca_id' => 1,
        //     'tanggal' => '2026-06-03',
        //     'jenis' => 'pengeluaran',
        //     'deskripsi' => 'Konsumsi rapat koordinasi',
        //     'bukti' => 'testing3.png',
        //     'kategori' => 'Konsumsi',
        //     'jumlah' => 180000,
        //     'saldo_setelah' => 1570000
        // ]);
        // $data_ca_transaction = tr_ca_transaction::create([
        //     'id' => 4,
        //     'tr_ca_id' => 1,
        //     'tanggal' => '2026-06-02',
        //     'jenis' => 'pengeluaran',
        //     'deskripsi' => 'Taksi ke lokasi proyek',
        //     'bukti' => 'testing4.png',
        //     'kategori' => 'Konsumsi',
        //     'jumlah' => 250000,
        //     'saldo_setelah' => 1750000
        // ]);

    }
}
