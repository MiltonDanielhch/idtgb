<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTransmision extends Model
{
    use HasFactory;

    protected $table = 'tipos_transmision';

    protected $fillable = [
        'nombre',
    ];

    public function tasas()
    {
        return $this->hasMany(Tasa::class);
    }

    public function tramites()
    {
        return $this->hasMany(Tramite::class);
    }
}
