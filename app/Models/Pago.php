<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'tramite_id',
        'fecha_pago',
        'monto',
        'codigo_barras',
        'nro_operacion',
        'banco',
        'estado',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto'      => 'decimal:2',
    ];

    /* ================== RELACIONES ================== */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    /* ================== HELPERS ================== */
    public function estaAplicado(): bool
    {
        return $this->estado === 'Aplicado';
    }
}
