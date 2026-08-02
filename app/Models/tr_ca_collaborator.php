<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tr_ca_collaborator extends Model
{
    use HasFactory;

    protected $table = 'tr_ca_collaborator';
    protected $guarded = [];

    public function ca()
    {
        return $this->belongsTo(tr_ca::class, 'tr_ca_id');
    }
}
