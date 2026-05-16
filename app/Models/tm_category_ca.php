<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tm_category_ca extends Model
{

    protected $table = 'tm_category_ca';
    use HasFactory;
    protected $fillable = ['name_category'];
    protected $guarded = [];

    public function tr_caPL()
    {
        return $this->hasMany(tr_ca::class);
    }
}
