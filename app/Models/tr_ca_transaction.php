<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tr_ca_transaction extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'tr_ca_transaction';

    protected $fillable = [
        'tr_ca_id',
        'tanggal',
        'jenis',
        'deskripsi',
        'bukti',
        'jumlah',
        'saldo_setelah',
        'kategori'
    ];

    // public function trCA()
    // {
    //     return $this->belongsTo(tr_ca::class);
    // }

    public function tr_ca()
    {
        return $this->belongsTo(tr_ca::class);
    }

    public function cashAdvance()
    {
        return $this->belongsTo(tr_ca::class, 'tr_ca_id', 'id');
    }
}
