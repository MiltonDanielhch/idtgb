<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parentesco extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parentescos';

    protected $fillable = [
        'nombre',
        'created_by',
        'updated_by',
    ];

    public function tasas()
    {
        return $this->hasMany(Tasa::class);
    }

    public function adquirentesTramite()
    {
        return $this->hasMany(AdquirenteTramite::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Define los grupos de parentescos según las tasas impositivas
     */
    public const GRUPO_LINEA_DIRECTA = 'linea_directa';

    public const GRUPO_COLATERAL = 'colateral';

    public const GRUPO_OTROS = 'otros';

    /**
     * Mapeo de parentescos a sus grupos correspondientes
     */
    public static function getGruposParentescos(): array
    {
        return [
            // Línea Directa (1%)
            self::GRUPO_LINEA_DIRECTA => [
                'nombres' => ['Cónyuge', 'Hijo/a', 'Padre', 'Madre', 'Nieto/a', 'Abuelo/a'],
                'label' => 'Línea Directa (1%)',
                'tasa' => 1,
                'icono' => 'fa-home',
                'color' => 'success',
            ],
            // Línea Colateral (10%)
            self::GRUPO_COLATERAL => [
                'nombres' => ['Hermano/a'],
                'label' => 'Línea Colateral (10%)',
                'tasa' => 10,
                'icono' => 'fa-users',
                'color' => 'warning',
            ],
            // Otros (20%)
            self::GRUPO_OTROS => [
                'nombres' => ['Tío/a', 'Sobrino/a', 'Legatario/a', 'Sin parentesco'],
                'label' => 'Otros (20%)',
                'tasa' => 20,
                'icono' => 'fa-user-plus',
                'color' => 'danger',
            ],
        ];
    }

    /**
     * Obtiene el grupo al que pertenece un parentesco por su nombre
     */
    public static function getGrupoPorNombre(string $nombre): string
    {
        $grupos = self::getGruposParentescos();

        foreach ($grupos as $grupoKey => $grupoData) {
            foreach ($grupoData['nombres'] as $nombreGrupo) {
                if (stripos($nombre, $nombreGrupo) !== false) {
                    return $grupoKey;
                }
            }
        }

        return self::GRUPO_OTROS;
    }

    /**
     * Obtiene parentescos agrupados para uso en selects
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $parentescos
     */
    public static function agruparParaSelect($parentescos = null): array
    {
        if ($parentescos === null) {
            $parentescos = self::all();
        }

        $grupos = self::getGruposParentescos();
        $resultado = [];

        foreach ($grupos as $grupoKey => $grupoData) {
            $resultado[$grupoKey] = [
                'label' => $grupoData['label'],
                'tasa' => $grupoData['tasa'],
                'icono' => $grupoData['icono'],
                'color' => $grupoData['color'],
                'parentescos' => [],
            ];
        }

        foreach ($parentescos as $parentesco) {
            $grupoKey = self::getGrupoPorNombre($parentesco->nombre);
            $resultado[$grupoKey]['parentescos'][] = $parentesco;
        }

        // Eliminar grupos vacíos
        return array_filter($resultado, function ($grupo) {
            return ! empty($grupo['parentescos']);
        });
    }
}
