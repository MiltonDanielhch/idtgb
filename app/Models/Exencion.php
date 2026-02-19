<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exencion extends Model
{
    use HasFactory, SoftDeletes;

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

    /**
     * Verificar si la exención está vigente para una fecha específica
     * 
     * @param string|null $fecha
     * @return bool
     */
    public function isVigente(?string $fecha = null): bool
    {
        $fecha = $fecha ?? today()->toDateString();
        
        if ($fecha < $this->vigente_desde->toDateString()) {
            return false;
        }
        
        if ($this->vigente_hasta !== null && $fecha > $this->vigente_hasta->toDateString()) {
            return false;
        }
        
        return true;
    }

    /**
     * Calcular el monto de la exención basado en una base imponible
     * Bug #5: Calcular automáticamente según tipo
     * 
     * @param float $baseImponible
     * @return float
     */
    public function calcularMonto(float $baseImponible): float
    {
        $montoCalculado = 0;

        if ($this->tipo === 'porcentaje') {
            $montoCalculado = ($baseImponible * $this->valor) / 100;
        } elseif ($this->tipo === 'monto_fijo') {
            $montoCalculado = $this->valor;
        }

        // Aplicar monto máximo si existe (Bug #2)
        if ($this->monto_maximo !== null && $montoCalculado > $this->monto_maximo) {
            $montoCalculado = $this->monto_maximo;
        }

        return round($montoCalculado, 2);
    }

    /**
     * Accessor para mostrar el valor formateado
     * 
     * @return string
     */
    public function getValorFormateadoAttribute(): string
    {
        if ($this->tipo === 'porcentaje') {
            return $this->valor . '%';
        }
        return 'Bs. ' . number_format($this->valor, 2);
    }

    /* ================== RELATIONS ================== */
    /**
     * The tramites that belong to the Exencion.
     */
    public function tramites()
    {
        // Defines a many-to-many relationship with the Tramite model.
        // 'tramite_exenciones' is the pivot table.
        // 'withPivot' allows access to extra columns on the pivot table, like 'monto_aplicado'.
        return $this->belongsToMany(Tramite::class, 'tramite_exenciones')->withPivot('monto_aplicado')->withTimestamps();
    }
}
