<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    use HasFactory;

    protected $table = 'provincias';

    protected $fillable = ['nombre', 'departamento_id'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($provincia) {
            if ($provincia->municipios()->exists()) {
                throw new \Exception('No se puede eliminar la provincia: tiene municipios asociados.');
            }
        });
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function municipios()
    {
        return $this->hasMany(Municipio::class);
    }
}
