<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistersUserEvents;

class Person extends Model
{
    use HasFactory, RegistersUserEvents, SoftDeletes;

    /* -------------------------------------------------
     *  CONFIGURACIÓN
     * ------------------------------------------------- */
    protected $table = 'people';
    protected $dates = ['deleted_at'];
    protected $casts = [
        //
    ];

    protected $fillable = [
        'person_type',
        'tipo_doc',
        'ci',
        'ci_complemento',
        'nit',
        'nombre_completo',
        'legal_name',
        'phone',
        'registerUser_id',
        'registerRole',
        'deleteUser_id',
        'deleteRole',
        'deleteObservation',
    ];

    protected $appends = [];
    

    /* -------------------------------------------------
     *  ACCESORES
     * ------------------------------------------------- */
    public function getFullNameAttribute(): string
    {
        if ($this->person_type === 'Jurídica') {
            return $this->legal_name ?? 'Sin razón social';
        }
        return $this->attributes['nombre_completo'] ?? 'Nombre no definido';
    }

    /**
     * Compatibilidad: alias en español para nombres completos usados en algunas vistas antiguas.
     * @return string
     */
    public function getNombreCompletoAttribute(): string
    {
        return $this->attributes['nombre_completo'] ?? '';
    }

    public function getDisplayNameAttribute()
    {
        if ($this->person_type === 'Jurídica') {
            return strtoupper($this->legal_name ?? 'Sin razón social');
        }

        return strtoupper($this->nombre_completo ?: 'Nombre no definido');
    }

    public function getDisplayDocumentAttribute()
    {
        if ($this->person_type === 'Jurídica') {
            return $this->nit ?: 'Sin NIT';
        }

        return $this->ci . ($this->ci_complemento ? ' ' . $this->ci_complemento : '') ?: 'Sin CI';
    }

    /* -------------------------------------------------
      *  SCOPES
      * ------------------------------------------------- */

    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('ci', 'like', "%{$search}%")
                ->orWhere('nit', 'like', "%{$search}%")
                ->orWhere('nombre_completo', 'like', "%{$search}%")
                ->orWhere('legal_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }



    /* -------------------------------------------------
     *  RELACIONES
     * ------------------------------------------------- */
    public function adquirentesTramite()
    {
        return $this->hasMany(AdquirenteTramite::class, 'person_id');
    }

    public function disponentesTramite()
    {
        return $this->hasMany(DisponenteTramite::class, 'person_id');
    }

    public function registerUser()
    {
        return $this->belongsTo(User::class, 'registerUser_id');
    }

    public function deleteUser()
    {
        return $this->belongsTo(User::class, 'deleteUser_id');
    }
}
