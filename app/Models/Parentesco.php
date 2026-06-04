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
        'categoria_tasa',
        'created_by',
        'updated_by',
    ];

    public const CATEGORIA_LINEA_DIRECTA = 1;
    public const CATEGORIA_COLATERAL = 10;
    public const CATEGORIA_OTROS = 20;

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
     * Obtiene el grupo al que pertenece un parentesco (prioriza campo categoria_tasa)
     */
    public static function getGrupoPorNombre(string $nombre, ?int $categoria = null): string
    {
        if ($categoria !== null) {
            return self::getGrupoPorCategoria($categoria);
        }

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

    public function getGrupoAttribute(): string
    {
        if ($this->categoria_tasa !== null) {
            return self::getGrupoPorCategoria($this->categoria_tasa);
        }
        return self::getGrupoPorNombre($this->nombre);
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
            $grupoKey = self::getGrupoPorNombre($parentesco->nombre, $parentesco->categoria_tasa);
            $resultado[$grupoKey]['parentescos'][] = $parentesco;
        }

        // Eliminar grupos vacíos
        return array_filter($resultado, function ($grupo) {
            return ! empty($grupo['parentescos']);
        });
    }

    public static function agruparPorCategorias(): array
    {
        try {
            self::where('categoria_tasa', 1)->firstOrFail();
            return self::getCategoriasParaSelect();
        } catch (\Exception $e) {
            return self::getCategoriasParaSelect();
        }
    }

    public static function getCategoriaPorGrupo(string $grupo): int
    {
        return match($grupo) {
            self::GRUPO_LINEA_DIRECTA => self::CATEGORIA_LINEA_DIRECTA,
            self::GRUPO_COLATERAL => self::CATEGORIA_COLATERAL,
            self::GRUPO_OTROS => self::CATEGORIA_OTROS,
            default => self::CATEGORIA_OTROS,
        };
    }

    public static function getGrupoPorCategoria(int $categoria): string
    {
        return match($categoria) {
            self::CATEGORIA_LINEA_DIRECTA => self::GRUPO_LINEA_DIRECTA,
            self::CATEGORIA_COLATERAL => self::GRUPO_COLATERAL,
            self::CATEGORIA_OTROS => self::GRUPO_OTROS,
            default => self::GRUPO_OTROS,
        };
    }

    public static function getPrimerParentescoPorCategoria(int $categoria): ?self
    {
        try {
            $parentesco = self::where('categoria_tasa', $categoria)->first();
            if ($parentesco) {
                return $parentesco;
            }
        } catch (\Exception $e) {
            // tabla no existe o error de conexión
        }

        return self::getPrimerParentescoPorNombre($categoria);
    }

    private static function getPrimerParentescoPorNombre(int $categoria): ?self
    {
        $nombres = match($categoria) {
            self::CATEGORIA_LINEA_DIRECTA => ['Cónyuge', 'Conviviente', 'Hijo'],
            self::CATEGORIA_COLATERAL => ['Hermano'],
            self::CATEGORIA_OTROS => ['Tío', 'Sin parentesco'],
            default => ['Sin parentesco'],
        };

        foreach ($nombres as $nombre) {
            $parentesco = self::where('nombre', 'like', "%{$nombre}%")->first();
            if ($parentesco) {
                return $parentesco;
            }
        }

        return self::first();
    }

    public static function getCategoriasParaSelect(): array
    {
        return [
            [
                'key' => self::CATEGORIA_LINEA_DIRECTA,
                'grupo' => self::GRUPO_LINEA_DIRECTA,
                'label' => 'Línea Directa (1%)',
                'tasa' => 1,
                'icono' => 'fa-home',
                'descripcion' => 'Cónyuge, Hijos, Padres, Abuelos, Nietos',
            ],
            [
                'key' => self::CATEGORIA_COLATERAL,
                'grupo' => self::GRUPO_COLATERAL,
                'label' => 'Línea Colateral (10%)',
                'tasa' => 10,
                'icono' => 'fa-users',
                'descripcion' => 'Hermanos',
            ],
            [
                'key' => self::CATEGORIA_OTROS,
                'grupo' => self::GRUPO_OTROS,
                'label' => 'Otros (20%)',
                'tasa' => 20,
                'icono' => 'fa-user-plus',
                'descripcion' => 'Tíos, Sobrinos, Sin parentesco',
            ],
        ];
    }
}
