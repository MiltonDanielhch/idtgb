<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inmueble extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inmuebles';

    protected $fillable = [
        'complemento',
        'catastro',
        'tipo_inmueble_id',
        'municipio_id',
        'barrio_comunidad',
        'direccion',
        'superficie_m2',
        'valor_catastral',
        'matricula_rr',
        'es_vivienda_unica_familiar',
        'estado_inmueble',
        'updated_by',
    ];

    protected $casts = [
        'es_vivienda_unica_familiar' => 'boolean',
        'superficie_m2'              => 'decimal:2',
        'valor_catastral'            => 'decimal:2',
    ];

    /* ================== RELACIONES ================== */
    // app/Models/Inmueble.php
    public function tramiteInmuebles()
    {
        return $this->hasMany(TramiteInmueble::class);
    }

    public function tipoInmueble()
    {
        return $this->belongsTo(TipoInmueble::class);
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    public function avaluos()
    {
        return $this->hasMany(Avaluo::class);
    }

    /* ================== HELPERS ================== */
    public function avaluoVigente()
    {
        return $this->avaluos()->where('estado', 'Vigente')->latest()->first();
    }
}
