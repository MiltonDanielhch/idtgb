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
        'hash_validacion',
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
        'hash_validacion' => 'string',
    ];

    /* ================== RELACIONES ================== */
    public function tipoTransmision()
    {
        return $this->belongsTo(TipoTransmision::class, 'tipo_transmision_id');
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

    public function inmuebles()
    {
        return $this->belongsToMany(
            Inmueble::class,
            'tramite_inmuebles',
            'tramite_id',
            'inmueble_id'
        );
    }

    public function exenciones()
    {
        return $this->belongsToMany(Exencion::class, 'tramite_exenciones')
                    ->withPivot('monto_aplicado');
    }

    // ✅ CORREGIDO - hasMany con modelos pivote
    public function adquirentes()
    {
        return $this->hasMany(AdquirenteTramite::class);
    }

    public function disponentes()
    {
        return $this->hasMany(DisponenteTramite::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    /* ================== RELACIONES INDIRECTAS ================== */

    /**
     * Obtener personas adquirentes a través del modelo pivote
     */
    public function personasAdquirentes()
    {
        return $this->hasManyThrough(
            Person::class,
            AdquirenteTramite::class,
            'tramite_id', // FK en adquirentes_tramite
            'id',         // FK en people
            'id',         // Local key en tramites
            'person_id'   // FK en adquirentes_tramite que apunta a people
        );
    }

    /**
     * Obtener personas disponentes a través del modelo pivote
     */
    public function personasDisponentes()
    {
        return $this->hasManyThrough(
            Person::class,
            DisponenteTramite::class,
            'tramite_id', // FK en disponentes_tramite
            'id',         // FK en people
            'id',         // Local key en tramites
            'person_id'   // FK en disponentes_tramite que apunta a people
        );
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

        $this->hash_validacion = hash('sha256', $this->id . '|' . $this->nro_tramite . '|' . now()->timestamp . '|' . Str::random(10));
        $this->save();

        return $this->hash_validacion;
    }

    /* ================== SCOPES ================== */

    public function scopeConRelacionesCompletas($query)
    {
        return $query->with([
            'tipoTransmision',
            'user',
            'inmuebles.tipoInmueble',
            'inmuebles.municipio.provincia.departamento',
            'adquirentes.person.municipio.provincia.departamento',
            'adquirentes.parentesco',
            'disponentes.person.municipio.provincia.departamento',
            'pagos',
            'documentos'
        ]);
    }

    /* ================== ACCESORES ================== */

    /**
     * Obtener adquirentes con información completa
     */
    public function getAdquirentesCompletosAttribute()
    {
        return $this->adquirentes()->with(['person.municipio.provincia.departamento', 'parentesco'])->get();
    }

    /**
     * Obtener disponentes con información completa
     */
    public function getDisponentesCompletosAttribute()
    {
        return $this->disponentes()->with(['person.municipio.provincia.departamento'])->get();
    }

    /**
     * Obtener inmuebles con información completa
     */
    public function getInmueblesCompletosAttribute()
    {
        return $this->inmuebles()->with(['tipoInmueble', 'municipio.provincia.departamento'])->get();
    }

    /**
     * Estado del trámite con color para UI
     */
    public function getEstadoColorAttribute()
    {
        return match($this->estado) {
            'Borrador' => 'secondary',
            'Pagado' => 'success',
            'Observado' => 'warning',
            'Anulado' => 'danger',
            'Finalizado' => 'info',
            default => 'secondary'
        };
    }
}
