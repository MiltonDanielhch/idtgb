<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exencion extends Model
{
    use HasFactory;

    protected $table = 'exenciones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'valor',
        'monto_maximo',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'monto_maximo' => 'decimal:2',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    /* ================== HELPERS ================== */
    public function scopeVigente($query, ?string $fecha = null)
    {
        $fecha = $fecha ?? today()->toDateString();
        return $query->where('vigente_desde', '<=', $fecha)
                     ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                           ->orWhere('vigente_hasta', '>=', $fecha));
    }
}
