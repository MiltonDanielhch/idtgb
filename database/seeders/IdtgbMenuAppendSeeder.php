<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;

class IdtgbMenuAppendSeeder extends Seeder
{
    protected $tree = [
    [
        'title'      => 'Personas',
        'order'      => 2,
        'icon_class' => 'voyager-person',
        'route'      => 'admin.people.index',
        'url'        => '',
    ],
    [
        'title'      => 'Catálogos IDTGB',
        'order'      => 3,
        'icon_class' => 'fa-solid fa-folder-tree',
        'route'      => null,
        'url'        => '',
        'children'   => [
            // ['title' => 'Departamentos',         'route' => 'admin.departamentos.index',       'icon_class' => 'fa-solid fa-map-location-dot',     'order' => 1],
            // ['title' => 'Provincias',            'route' => 'admin.provincias.index',          'icon_class' => 'fa-solid fa-map-pin',              'order' => 2],
            // ['title' => 'Municipios',            'route' => 'admin.municipios.index',          'icon_class' => 'fa-solid fa-city',                 'order' => 3],
            ['title' => 'Parentescos',           'route' => 'admin.parentescos.index',         'icon_class' => 'fa-solid fa-people-group',         'order' => 4],
            // ['title' => 'Tipos de Transmisión',  'route' => 'admin.tipos-transmision.index',   'icon_class' => 'fa-solid fa-arrow-right-arrow-left','order'=>5],
            // ['title' => 'Tipos de Inmueble',     'route' => 'admin.tipos-inmueble.index',      'icon_class' => 'fa-solid fa-house-chimney',        'order' => 6],
            ['title' => 'Tasas',                 'route' => 'admin.tasas.index',               'icon_class' => 'fa-solid fa-percent',              'order' => 7],
            ['title' => 'Exenciones',            'route' => 'admin.exenciones.index',          'icon_class' => 'fa-solid fa-gift',                 'order' => 8],
            ['title' => 'UFVs', 'route' => 'admin.ufvs.index', 'icon_class' => 'fa-solid fa-calendar-day', 'order' => 9],
        ],
    ],
    [
        'title'      => 'Inmuebles',
        'order'      => 4,
        'icon_class' => 'fa-solid fa-building',
        'route'      => null,
        'url'        => '',
        'children'   => [
            ['title' => 'Inmuebles', 'route' => 'admin.inmuebles.index', 'icon_class' => 'fa-solid fa-home',              'order' => 1],
            ['title' => 'Avalúos',   'route' => 'admin.avaluos.index',   'icon_class' => 'fa-solid fa-file-invoice-dollar','order'=>2],
        ],
    ],
    [
        'title'      => 'Trámites IDTGB',
        'order'      => 5,
        'icon_class' => 'fa-solid fa-file-lines',
        'route'      => null,
        'url'        => '',
        'children'   => [
            ['title' => 'Trámites',            'route' => 'admin.tramites.index',            'icon_class' => 'fa-solid fa-folder-open',     'order' => 1],
        ],
    ],
    [
        'title'      => 'Reportes',
        'order'      => 6,
        'icon_class' => 'voyager-bar-chart',
        'route'      => 'admin.reportes.index',
        'url'        => '',
    ],
];
    public function run()
    {
        $menu = Menu::where('name', 'admin')->firstOrFail();

        foreach ($this->tree as $root) {
            $this->createRecursive($menu, $root);
        }
    }

    private function createRecursive($menu, $item, $parentId = null)
    {
        $data = [
            'menu_id'   => $menu->id,
            'parent_id' => $parentId,
            'title'     => $item['title'],
            'url'       => $item['url'] ?? '',
            'route'     => $item['route'] ?? null,
            'parameters'=> '',
            'target'    => '_self',
            'icon_class'=> $item['icon_class'],
            'color'     => null,
            'order'     => $item['order'],
        ];

        $dbItem = MenuItem::firstOrCreate(
            ['menu_id' => $menu->id, 'title' => $item['title'], 'parent_id' => $parentId],
            $data
        );

        foreach ($item['children'] ?? [] as $child) {
            $this->createRecursive($menu, $child, $dbItem->id);
        }
    }
}