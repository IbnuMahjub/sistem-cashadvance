<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tr_ca extends Model
{
    use HasFactory;


    protected $table = 'tr_ca';
    protected $guarded = [];
    protected $fillable = ['kode_ca', 'judul_kegiatan', 'tahun_anggaran', 'tanggal_mulai', 'tanggal_selesai', 'total_penerimaan', 'total_pengeluaran', 'saldo_akhir', 'status', 'created_by'];

    public function trCA()
    {
        return $this->hasMany(tr_ca_transaction::class);
    }

    public function tm_category_ca()
    {
        return $this->belongsTo(tm_category_ca::class, 'id_ca_category');
    }
}
