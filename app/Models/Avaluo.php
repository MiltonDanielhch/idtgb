<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avaluo extends Model
{
    use HasFactory;

    protected $table = 'avaluos';

    protected $fillable = [
        'inmueble_id',
        'tipo_avaluo',
        'fecha_avaluo',
        'valor',
        'perito_id',
        'documento_path',
        'estado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'valor'        => 'decimal:2',
        'fecha_avaluo' => 'date',
        'estado'       => 'string',
    ];

    /* ================== RELACIONES ================== */
    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }

    public function perito()
    {
        return $this->belongsTo(Person::class, 'perito_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ================== HELPERS ================== */
    public static function vigente(int $inmuebleId): ?self
    {
        return self::where('inmueble_id', $inmuebleId)
                   ->where('estado', 'Vigente')
                   ->latest('fecha_avaluo')
                   ->first();
    }
}
