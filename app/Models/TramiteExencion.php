<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TramiteExencion extends Model
{
    use HasFactory, SoftDeletes;

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

    /* ================== HELPERS ================== */
    /**
     * Verificar si la exención está vigente para la fecha del trámite
     * 
     * @return bool
     */
    public function isVigente(): bool
    {
        if (!$this->tramite || !$this->exencion) {
            return false;
        }
        
        return $this->exencion->isVigente($this->tramite->fecha_presentacion->toDateString());
    }

    /**
     * Accessor para mostrar el monto formateado
     * 
     * @return string
     */
    public function getMontoFormateadoAttribute(): string
    {
        return 'Bs. ' . number_format($this->monto_aplicado, 2);
    }
}
