<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TramiteInmueble extends Model
{
    protected $table = 'tramite_inmuebles';

    protected $fillable = [
        'tramite_id',
        'inmueble_id',
    ];

    /* ---------------- relaciones ---------------- */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }
}
