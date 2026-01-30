<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ufv extends Model
{
    use HasFactory;

    protected $table = 'ufvs';

    protected $fillable = [
        'fecha',
        'valor',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:5'
    ];

    /**
     * Obtiene el valor de la UFV para una fecha específica.
     * Implementa lógica de búsqueda hacia atrás si la fecha exacta no existe.
     * * @param mixed $fecha
     * @return float
     */
    public static function getValorEnFecha($fecha)
    {
        // 1. Intentar obtener el valor exacto o el inmediato anterior
        $ufv = self::where('fecha', '<=', $fecha)
                   ->orderBy('fecha', 'desc')
                   ->first();

        // 2. Si no existe ningún valor en la base de datos, devolvemos 1.0
        // para evitar errores matemáticos de división entre cero.
        if (!$ufv) {
            return 1.00000;
        }

        return (float) $ufv->valor;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
