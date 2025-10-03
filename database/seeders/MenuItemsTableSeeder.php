<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MenuItemsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('menu_items')->delete();

        \DB::table('menu_items')->insert(array (
            0 =>
            array (
                'id' => 1,
                'menu_id' => 1,
                'title' => 'Inicio',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'fa-solid fa-house',
                'color' => '#000000',
                'parent_id' => NULL,
                'order' => 1,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2024-10-18 16:31:13',
                'route' => 'voyager.dashboard',
                'parameters' => 'null',
            ),
            1 =>
            array (
                'id' => 2,
                'menu_id' => 1,
                'title' => 'Media',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-images',
                'color' => NULL,
                'parent_id' => 5,
                'order' => 5,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2024-10-18 16:40:50',
                'route' => 'voyager.media.index',
                'parameters' => NULL,
            ),
            2 =>
            array (
                'id' => 3,
                'menu_id' => 1,
                'title' => 'Usuarios',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-person',
                'color' => '#000000',
                'parent_id' => 14,
                'order' => 1,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2024-10-18 16:33:38',
                'route' => 'voyager.users.index',
                'parameters' => 'null',
            ),
            3 =>
            array (
                'id' => 4,
                'menu_id' => 1,
                'title' => 'Roles',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-lock',
                'color' => NULL,
                'parent_id' => 14,
                'order' => 2,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2024-10-18 16:33:29',
                'route' => 'voyager.roles.index',
                'parameters' => NULL,
            ),
            4 =>
            array (
                'id' => 5,
                'menu_id' => 1,
                'title' => 'Herramientas',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-tools',
                'color' => '#000000',
                'parent_id' => NULL,
                'order' => 7,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2025-02-10 15:42:56',
                'route' => NULL,
                'parameters' => '',
            ),
            5 =>
            array (
                'id' => 6,
                'menu_id' => 1,
                'title' => 'Menu Builder',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-list',
                'color' => NULL,
                'parent_id' => 5,
                'order' => 1,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2024-10-18 16:33:17',
                'route' => 'voyager.menus.index',
                'parameters' => NULL,
            ),
            6 =>
            array (
                'id' => 7,
                'menu_id' => 1,
                'title' => 'Database',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-data',
                'color' => NULL,
                'parent_id' => 5,
                'order' => 2,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2024-10-18 16:40:50',
                'route' => 'voyager.database.index',
                'parameters' => NULL,
            ),
            7 =>
            array (
                'id' => 8,
                'menu_id' => 1,
                'title' => 'Compass',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-compass',
                'color' => NULL,
                'parent_id' => 5,
                'order' => 3,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2024-10-18 16:40:50',
                'route' => 'voyager.compass.index',
                'parameters' => NULL,
            ),
            8 =>
            array (
                'id' => 9,
                'menu_id' => 1,
                'title' => 'BREAD',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-bread',
                'color' => NULL,
                'parent_id' => 5,
                'order' => 4,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2024-10-18 16:40:50',
                'route' => 'voyager.bread.index',
                'parameters' => NULL,
            ),
            9 =>
            array (
                'id' => 10,
                'menu_id' => 1,
                'title' => 'Configuración',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-settings',
                'color' => '#000000',
                'parent_id' => NULL,
                'order' => 8,
                'created_at' => '2024-10-18 14:28:27',
                'updated_at' => '2025-02-10 15:42:56',
                'route' => 'voyager.settings.index',
                'parameters' => 'null',
            ),
            10 =>
            array (
                'id' => 11,
                'menu_id' => 1,
                'title' => 'Categories',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-categories',
                'color' => NULL,
                'parent_id' => NULL,
                'order' => 4,
                'created_at' => '2024-10-18 14:28:45',
                'updated_at' => '2025-02-10 15:42:59',
                'route' => 'voyager.categories.index',
                'parameters' => NULL,
            ),
            11 =>
            array (
                'id' => 12,
                'menu_id' => 1,
                'title' => 'Posts',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-news',
                'color' => NULL,
                'parent_id' => NULL,
                'order' => 2,
                'created_at' => '2024-10-18 14:28:45',
                'updated_at' => '2024-10-18 16:40:42',
                'route' => 'voyager.posts.index',
                'parameters' => NULL,
            ),
            12 =>
            array (
                'id' => 13,
                'menu_id' => 1,
                'title' => 'Pages',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'voyager-file-text',
                'color' => NULL,
                'parent_id' => NULL,
                'order' => 3,
                'created_at' => '2024-10-18 14:28:46',
                'updated_at' => '2024-10-18 16:40:42',
                'route' => 'voyager.pages.index',
                'parameters' => NULL,
            ),
            13 =>
            array (
                'id' => 14,
                'menu_id' => 1,
                'title' => 'Seguridad',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'fa-solid fa-lock',
                'color' => '#000000',
                'parent_id' => NULL,
                'order' => 6,
                'created_at' => '2024-10-18 16:33:05',
                'updated_at' => '2025-02-10 15:42:56',
                'route' => NULL,
                'parameters' => '',
            ),
            14 =>
            array (
                'id' => 15,
                'menu_id' => 1,
                'title' => 'Limpiar cache',
                'url' => '',
                'target' => '_self',
                'icon_class' => 'fa-solid fa-broom',
                'color' => '#000000',
                'parent_id' => NULL,
                'order' => 9,
                'created_at' => '2024-12-09 12:33:25',
                'updated_at' => '2025-02-10 15:42:56',
                'route' => 'clear.cache',
                'parameters' => NULL,
            ),
        ));


    }
}
