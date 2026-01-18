<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoInmueble extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_inmueble';

    protected $fillable = [
        'nombre',
    ];

    public function inmuebles()
    {
        return $this->hasMany(Inmueble::class);
    }
}
