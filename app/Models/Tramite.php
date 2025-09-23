<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tramite extends Model
{
    use HasFactory;

    protected $table = 'tramites';

    protected $fillable = [
        'nro_tramite',
        'fecha_presentacion',
        'tipo_transmision_id',
        'inmueble_id',
        'valor_declarado',
        'base_imponible',
        'total_idtgb',
        'recargo_mora',
        'monto_final',
        'estado',
        'fecha_transmision',
        'fecha_vencimiento',
        'observaciones',
        'user_id',
    ];

    protected $casts = [
        'valor_declarado' => 'decimal:2',
        'base_imponible'  => 'decimal:2',
        'total_idtgb'     => 'decimal:2',
        'recargo_mora'    => 'decimal:2',
        'monto_final'     => 'decimal:2',
        'fecha_presentacion' => 'date',
        'fecha_transmision'  => 'date',
        'fecha_vencimiento'  => 'date',
    ];

    /* ================== RELACIONES ================== */
    public function tipoTransmision()
    {
        return $this->belongsTo(TipoTransmision::class);
    }

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adquirentes()
    {
        return $this->hasMany(AdquirenteTramite::class);
    }

    public function disponentes()
    {
        return $this->hasMany(DisponenteTramite::class);
    }

    public function tramiteExenciones()
    {
        return $this->hasMany(TramiteExencion::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    /* ================== HELPERS ================== */
    public function calcularMora(): float
    {
        if ($this->estado === 'Pagado') return 0.00;

        $dias = $this->fecha_vencimiento->diffInDays(today(), false);
        if ($dias <= 0) return 0.00;

        $meses = intdiv($dias, 30);
        $tasa  = 0.005; // 0,5 % mensual
        $mora  = $this->base_imponible * $tasa * $meses;

        return min($mora, $this->base_imponible * 0.50); // tope 50 %
    }
}
