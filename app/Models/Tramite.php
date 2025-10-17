<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tramite extends Model
{
    use HasFactory;

    protected $table = 'tramites';

    protected $fillable = [
        'nro_tramite',
        'fecha_presentacion',
        'tipo_transmision_id',
        'valor_declarado',
        'base_imponible',
        'total_idtgb',
        'recargo_mora',
        'monto_final',
        'ufv_aplicada',
        'estado',
        'fecha_transmision',
        'fecha_vencimiento',
        'observaciones',
        'user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_presentacion' => 'date',
        'fecha_transmision' => 'date',
        'fecha_vencimiento' => 'date',
        'valor_declarado' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'total_idtgb' => 'decimal:2',
        'recargo_mora' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'ufv_aplicada' => 'decimal:5',
    ];

    /* ================== RELACIONES ================== */
 public function tipoTransmision()
    {
        return $this->belongsTo(TipoTransmision::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // public function inmuebles()
    // {
    //     return $this->belongsToMany(Inmueble::class, 'tramite_inmuebles');
    // }
    public function inmuebles()
    {
        return $this->belongsToMany(
            Inmueble::class,
            'tramite_inmuebles', // nombre de la tabla pivote
            'tramite_id',        // FK de trámite en la pivote
            'inmueble_id'        // FK de inmueble en la pivote
        );
    }

    public function exenciones()
    {
        return $this->belongsToMany(Exencion::class, 'tramite_exenciones')
                    ->withPivot('monto_aplicado');
    }

    public function adquirentes()
    {
        return $this->belongsToMany(Person::class, 'adquirentes_tramite', 'tramite_id', 'person_id');
    }

    public function disponentes()
    {
        return $this->belongsToMany(Person::class, 'disponentes_tramite', 'tramite_id', 'person_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
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

    public function generateHashValidacion(): string
    {
        if ($this->hash_validacion) {
            return $this->hash_validacion;
        }

        // Genera un hash único combinando datos del trámite y un elemento aleatorio
        $this->hash_validacion = hash('sha256', $this->id . '|' . $this->nro_tramite . '|' . now()->timestamp . '|' . Str::random(10));
        $this->save();

        return $this->hash_validacion;
    }
}