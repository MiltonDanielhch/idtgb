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
        'tipo_transmision_id',
        'tasa',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected $casts = [
        'tasa' => 'decimal:2',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function parentesco()
    {
        return $this->belongsTo(Parentesco::class);
    }

    public function tipoTransmision()
    {
        return $this->belongsTo(TipoTransmision::class, 'tipo_transmision_id');
    }

    /* ==================  HELPERS  ================== */
    public static function vigente(int $departamentoId, int $parentescoId, ?string $fecha = null, ?int $tipoTransmisionId = null): ?self
    {
        $fecha = $fecha ?? today()->toDateString();

        $query = self::where('departamento_id', $departamentoId)
                   ->where('parentesco_id', $parentescoId)
                   ->where('vigente_desde', '<=', $fecha)
                   ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                         ->orWhere('vigente_hasta', '>=', $fecha));

        if ($tipoTransmisionId !== null) {
            $query->where(function ($q) use ($tipoTransmisionId) {
                $q->where('tipo_transmision_id', $tipoTransmisionId)
                  ->orWhereNull('tipo_transmision_id');
            })->orderBy('tipo_transmision_id', 'desc');
        }

        return $query->first();
    }

    public static function findApplicableRate(int $departamentoId, int $parentescoId, int $tipoTransmisionId, ?string $fecha = null): ?self
    {
        $fecha = $fecha ?? today()->toDateString();

        return self::where('departamento_id', $departamentoId)
                   ->where('parentesco_id', $parentescoId)
                   ->where('tipo_transmision_id', $tipoTransmisionId)
                   ->where('vigente_desde', '<=', $fecha)
                   ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                         ->orWhere('vigente_hasta', '>=', $fecha))
                   ->latest('vigente_desde')
                   ->first();
    }
}
