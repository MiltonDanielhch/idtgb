<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tasa extends Model
{
    use HasFactory;

    protected $table = 'tasas';

    protected $fillable = [
        'departamento_id',
        'parentesco_id',
        'tasa',
        'vigente_desde',
        'vigente_hasta',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function parentesco()
    {
        return $this->belongsTo(Parentesco::class);
    }

    /* ==================  HELPERS  ================== */
    public static function vigente(int $departamentoId, int $parentescoId, ?string $fecha = null): ?self
    {
        $fecha = $fecha ?? today()->toDateString();

        return self::where('departamento_id', $departamentoId)
                   ->where('parentesco_id', $parentescoId)
                   ->where('vigente_desde', '<=', $fecha)
                   ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                         ->orWhere('vigente_hasta', '>=', $fecha))
                   ->first();
    }
}
