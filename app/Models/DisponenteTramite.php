<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisponenteTramite extends Model
{
    use HasFactory;

    protected $table = 'disponentes_tramite';

    protected $fillable = [
        'tramite_id',
        'person_id',
        'tipo',
        'fecha_fallecimiento',
        'es_discapacitado',
    ];

    protected $casts = [
        'fecha_fallecimiento' => 'date',
        'es_discapacitado'    => 'boolean',
    ];

    /* ================== RELACIONES ================== */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function persona()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
