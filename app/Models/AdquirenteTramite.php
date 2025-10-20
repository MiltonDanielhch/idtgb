<?php
// app/Models/AdquirenteTramite.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdquirenteTramite extends Model
{
    use HasFactory;

    protected $table = 'adquirentes_tramite';

    protected $fillable = [
        'tramite_id',
        'person_id',
        'parentesco_id',
        'tasa_aplicada',
        'porcentaje',
        'idtgb_proporcional',
        'es_beneficiario_exencion',
        'documento_sustento_exencion',
    ];

    protected $casts = [
        'tasa_aplicada'     => 'decimal:2',
        'porcentaje'        => 'decimal:2',
        'idtgb_proporcional'=> 'decimal:2',
        'es_beneficiario_exencion' => 'boolean',
    ];

    /* ================== RELACIONES ================== */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    // ✅ Usar 'person' para consistencia
    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function parentesco()
    {
        return $this->belongsTo(Parentesco::class);
    }
}
