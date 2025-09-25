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
        'conciliado_el',
        'banco',
        'estado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'conciliado_el' => 'datetime',
        'monto' => 'decimal:2',
    ];

    /* ================== RELACIONES ================== */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ================== HELPERS ================== */
    public function estaAplicado(): bool
    {
        return $this->estado === 'Aplicado';
    }
}
