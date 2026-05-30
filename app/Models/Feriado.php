<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feriado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'feriados';

    protected $fillable = [
        'fecha',
        'nombre',
        'departamento_id',
        'tipo',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'activo' => 'boolean',
    ];

    public const TIPO_NACIONAL = 'Nacional';
    public const TIPO_DEPARTAMENTAL = 'Departamental';
    public const TIPO_MUNICIPAL = 'Municipal';

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Scope para filtrar feriados activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar feriados nacionales
     */
    public function scopeNacionales($query)
    {
        return $query->where('tipo', self::TIPO_NACIONAL);
    }

    /**
     * Scope para filtrar feriados de un departamento específico
     */
    public function scopeDepartamento($query, $departamentoId)
    {
        return $query->where('departamento_id', $departamentoId);
    }

    /**
     * Verificar si una fecha es feriado para un departamento específico
     */
    public static function esFeriado($fecha, $departamentoId = null)
    {
        $query = self::where('fecha', $fecha)
            ->where('activo', true);

        if ($departamentoId) {
            $query->where(function ($q) use ($departamentoId) {
                $q->whereNull('departamento_id')
                  ->orWhere('departamento_id', $departamentoId);
            });
        } else {
            $query->whereNull('departamento_id'); // Solo nacionales
        }

        return $query->exists();
    }

    /**
     * Obtener feriados en un rango de fechas
     */
    public static function obtenerFeriadosEnRango($fechaInicio, $fechaFin, $departamentoId = null)
    {
        $query = self::whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('activo', true);

        if ($departamentoId) {
            $query->where(function ($q) use ($departamentoId) {
                $q->whereNull('departamento_id')
                  ->orWhere('departamento_id', $departamentoId);
            });
        }

        return $query->orderBy('fecha')->get();
    }
}
