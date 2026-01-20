<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Municipio extends Model
{
    use HasFactory;

    protected $table = 'municipios';

    protected $fillable = ['nombre', 'provincia_id', 'codigo'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($municipio) {
            if ($municipio->inmuebles()->exists()) {
                throw new \Exception('No se puede eliminar el municipio: tiene inmuebles asociados.');
            }

            if ($municipio->personas()->exists()) {
                throw new \Exception('No se puede eliminar el municipio: tiene personas asociadas.');
            }
        });
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function inmuebles()
    {
        return $this->hasMany(Inmueble::class);
    }

    public function personas()
    {
        return $this->hasMany(Person::class);
    }

    public function scopeByDepartamento($query, $departamentoId)
    {
        return $query->whereHas('provincia', fn($q) => $q->where('departamento_id', $departamentoId));
    }

    public function scopeByProvincia($query, $provinciaId)
    {
        return $query->where('provincia_id', $provinciaId);
    }

    public static function getForSelect()
    {
        return self::select('id', 'nombre', 'provincia_id')
            ->with(['provincia:id,nombre,departamento_id', 'provincia.departamento:id,nombre,codigo'])
            ->orderBy('nombre')
            ->get();
    }

    public static function getCachedForSelect()
    {
        return Cache::remember('municipios.all_with_relations', 3600, function () {
            return self::getForSelect();
        });
    }

    public static function clearCache()
    {
        Cache::forget('municipios.all_with_relations');
    }
}
