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
    protected $dates = ['deleted_at', 'birth_date'];
    protected $casts = [
        'status' => 'integer',
        'birth_date' => 'date',
    ];

    protected $fillable = [
        'person_type',
        'tipo_doc',
        'ci',
        'ci_complemento',
        'nit',
        'first_name',
        'middle_name',
        'paternal_surname',
        'maternal_surname',
        'legal_name',
        'birth_date',
        'email',
        'phone',
        'address',
        'municipio_id',
        'gender',
        'image',
        'status',
        'estado_persona',
        'registerUser_id',
        'registerRole',
        'deleteUser_id',
        'deleteRole',
        'deleteObservation',
    ];

      // Agregar los accesores a los appends
    protected $appends = ['ubicacion_completa', 'ubicacion_segura'];
    
    /* -------------------------------------------------
     *  CONSTANTES
     * ------------------------------------------------- */
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;
    const STATUS_PENDING  = 2;

    public static function getStatusLabel($status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE   => 'Activo',
            self::STATUS_INACTIVE => 'Inactivo',
            self::STATUS_PENDING  => 'Pendiente',
            default               => 'Desconocido',
        };
    }

    /* -------------------------------------------------
     *  ACCESORES
     * ------------------------------------------------- */
    public function getFullNameAttribute(): string
    {
        return trim(
            collect([
                $this->first_name,
                $this->middle_name,
                $this->paternal_surname,
                $this->maternal_surname,
            ])->filter()->join(' ')
        );
    }
    // En el modelo Person
    public function getDisplayImageAttribute()
    {
        return $this->image ? asset('storage/'.$this->image) : asset('images/default.jpg');
    }

    public function getDisplayNameAttribute()
    {
        if ($this->person_type === 'Jurídica') {
            return strtoupper($this->legal_name ?? 'Sin razón social');
        }

        return strtoupper($this->full_name ?: 'Nombre no definido');
    }

    public function getDisplayDocumentAttribute()
    {
        if ($this->person_type === 'Jurídica') {
            return $this->nit ?: 'Sin NIT';
        }

        return $this->ci . ($this->ci_complemento ? ' ' . $this->ci_complemento : '') ?: 'Sin CI';
    }

    public function getDisplayAgeAttribute()
    {
        if (!$this->birth_date) return '-';

        return \Carbon\Carbon::parse($this->birth_date)->age . ' años';
    }

    public function getFormattedBirthDateAttribute()
    {
        return $this->birth_date ? $this->birth_date->format('d/m/Y') : null;
    }
    /* -------------------------------------------------
     *  SCOPES
     * ------------------------------------------------- */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }


     /**
     * Relación con municipio
     */
    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }
  /**
     * Accesor para la ubicación completa
     */
    public function getUbicacionCompletaAttribute()
    {
        if (!$this->municipio) {
            return 'Ubicación no especificada';
        }

        $ubicacion = $this->municipio->nombre;

        if ($this->municipio->provincia) {
            $ubicacion .= ', ' . $this->municipio->provincia->nombre;
        }

        if ($this->municipio->provincia && $this->municipio->provincia->departamento) {
            $ubicacion .= ', ' . $this->municipio->provincia->departamento->nombre;
        }

        return $ubicacion;
    }

    /**
     * Accesor para mostrar información de ubicación segura
     */
    public function getUbicacionSeguraAttribute()
    {
        try {
            return $this->ubicacion_completa;
        } catch (\Exception $e) {
            return 'Ubicación no disponible';
        }
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
