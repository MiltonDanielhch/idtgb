<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';

    protected $fillable = ['nombre', 'codigo'];

    const CODIGO_BENI = 'BE';
    const CODIGO_SANTA_CRUZ = 'SC';
    const CODIGO_LA_PAZ = 'LP';
    const CODIGO_COCHABAMBA = 'CB';
    const CODIGO_ORURO = 'OR';
    const CODIGO_POTOSI = 'PT';
    const CODIGO_TARIJA = 'TJ';
    const CODIGO_CHUQUISACA = 'CH';
    const CODIGO_PANDO = 'PA';

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($departamento) {
            if ($departamento->provincias()->exists()) {
                throw new \Exception('No se puede eliminar el departamento: tiene provincias asociadas.');
            }

            if ($departamento->tasas()->exists()) {
                throw new \Exception('No se puede eliminar el departamento: tiene tasas asociadas.');
            }
        });
    }

    public function provincias()
    {
        return $this->hasMany(Provincia::class);
    }

    public function municipios()
    {
        return $this->hasManyThrough(Municipio::class, Provincia::class);
    }

    public function tasas()
    {
        return $this->hasMany(Tasa::class);
    }
}
