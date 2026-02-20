<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tr_ca extends Model
{
    use HasFactory;


    protected $table = 'tr_ca';
    protected $fillable = ['kode_ca'];

    public function trCA()
    {
        return $this->hasMany(tr_ca_transaction::class);
    }
}
