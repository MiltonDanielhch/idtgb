<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Permission;

class PermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('permissions')->delete();

        Permission::firstOrCreate([
            'key'        => 'browse_admin',
            'keyDescription'=>'vista de acceso al sistema',
            'table_name' => 'admin',
            'tableDescription'=>'Panel del Sistema'
        ]);

        $keys = [
            // 'browse_admin',
            'browse_bread',
            'browse_database',
            'browse_media',
            'browse_compass',
            'browse_clear-cache',
        ];

        foreach ($keys as $key) {
            Permission::firstOrCreate([
                'key'        => $key,
                'table_name' => null,
            ]);
        }

        Permission::generateFor('menus');

        Permission::generateFor('roles');
        Permission::generateFor('permissions');
        Permission::generateFor('settings');

        Permission::generateFor('users');

        Permission::generateFor('posts');
        Permission::generateFor('categories');
        Permission::generateFor('pages');



        // Administracion
        $permissions = [
            'browse_people' => 'Ver lista de personas',
            'read_people' => 'Ver detalles de una persona',
            'edit_people' => 'Editar información de personas',
            'add_people' => 'Agregar nuevas personas',
            'delete_people' => 'Eliminar personas',
        ];

        foreach ($permissions as $key => $description) {
            Permission::firstOrCreate([
                'key'        => $key,
                'keyDescription'=> $description,
                'table_name' => 'people',
                'tableDescription'=>'Personas'
            ]);
        }


        // Parentescos
        $permissionsParentesco = [
            'browse_parentescos' => 'Ver lista de parentescos',
            'read_parentescos' => 'Ver detalles de un parentesco',
            'edit_parentescos' => 'Editar información de parentescos',
            'add_parentescos' => 'Agregar nuevos parentescos',
            'delete_parentescos' => 'Eliminar parentescos',
        ];

        foreach ($permissionsParentesco as $key => $description) {
            Permission::firstOrCreate([
                'key'        => $key,
                'keyDescription'=> $description,
                'table_name' => 'parentescos',
                'tableDescription'=>'Parentescos'
            ]);
        }
    }
}
