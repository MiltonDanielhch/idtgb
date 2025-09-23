<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TramiteExencion extends Model
{
    use HasFactory;

    protected $table = 'tramite_exenciones';

    protected $fillable = [
        'tramite_id',
        'exencion_id',
        'monto_aplicado',
    ];

    protected $casts = [
        'monto_aplicado' => 'decimal:2',
    ];

    /* ================== RELACIONES ================== */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function exencion()
    {
        return $this->belongsTo(Exencion::class);
    }
}
