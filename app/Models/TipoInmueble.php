<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoInmueble extends Model
{
    use HasFactory;

    protected $table = 'tipos_inmueble';

    protected $fillable = [
        'nombre',
    ];
}
