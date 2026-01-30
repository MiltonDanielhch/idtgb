<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';

    protected $fillable = [
        'tramite_id',
        'tipo_doc',
        'archivo_path',
        'descripcion',
        'hash_sha256',
        'person_id',
        'vigente',
        'version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vigente' => 'boolean',
        'version' => 'integer',
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

    /* ================== HELPERS ================== */
    public function estaVigente(): bool
    {
        return $this->vigente;
    }

    public function marcaComoCaducado(): void
    {
        $this->update(['vigente' => false]);
    }
}
