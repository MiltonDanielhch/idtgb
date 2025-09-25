<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ufv extends Model
{
    use HasFactory;

    protected $table = 'ufvs';

    protected $fillable = ['fecha', 'valor'];

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:5',
    ];
}
