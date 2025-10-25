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

        // Exenciones
        $permissionsExencion = [
            'browse_exenciones' => 'Ver lista de exenciones',
            'read_exenciones'   => 'Ver detalles de una exención',
            'edit_exenciones'   => 'Editar información de exenciones',
            'add_exenciones'    => 'Agregar nuevas exenciones',
            'delete_exenciones' => 'Eliminar exenciones',
        ];

        foreach ($permissionsExencion as $key => $description) {
            Permission::firstOrCreate([
                'key'            => $key,
                'keyDescription' => $description,
                'table_name'     => 'exenciones',
                'tableDescription'=> 'Exenciones IDTGB'
            ]);
        }

        // Tipos de Transmisión
        Permission::generateFor('tipos-transmision');

        // Tipos de Inmueble
        Permission::generateFor('tipos-inmueble');

        // Tasas
        Permission::generateFor('tasas');

        // Inmuebles
        Permission::generateFor('inmuebles');

        // Avaluos
        Permission::generateFor('avaluos');

        // Trámites
        $permissionsTramite = [
            'browse_tramites' => 'Ver lista de trámites',
            'read_tramites'   => 'Ver detalles de un trámite',
            'edit_tramites'   => 'Editar información de trámites',
            'add_tramites'    => 'Agregar nuevos trámites (usando el asistente)',
            'delete_tramites' => 'Eliminar trámites',
        ];
        foreach ($permissionsTramite as $key => $description) {
            Permission::firstOrCreate([
                'key'            => $key,
                'keyDescription' => $description,
                'table_name'     => 'tramites',
                'tableDescription'=> 'Trámites IDTGB'
            ]);
        }

        // Pagos (dentro de trámites)
        $permissionsPagos = [
            'browse_pagos' => 'Ver lista de pagos de un trámite',
            'read_pagos'   => 'Ver detalles de un pago',
            'edit_pagos'   => 'Editar información de pagos',
            'add_pagos'    => 'Agregar nuevos pagos',
            'delete_pagos' => 'Eliminar pagos',
        ];
        foreach ($permissionsPagos as $key => $description) {
            Permission::firstOrCreate([
                'key'            => $key,
                'keyDescription' => $description,
                'table_name'     => 'pagos',
                'tableDescription'=> 'Pagos de Trámites'
            ]);
        }

        // Documentos (dentro de trámites)
        $permissionsDocumentos = [
            'browse_documentos' => 'Ver lista de documentos de un trámite',
            'read_documentos'   => 'Ver detalles de un documento',
            'add_documentos'    => 'Agregar nuevos documentos',
            'delete_documentos' => 'Eliminar documentos',
        ];
        foreach ($permissionsDocumentos as $key => $description) {
            Permission::firstOrCreate([
                'key'            => $key,
                'keyDescription' => $description,
                'table_name'     => 'documentos',
                'tableDescription'=> 'Documentos de Trámites'
            ]);
        }

        // UFVs
        $permissionsUfv = [
            'browse_ufvs' => 'Ver lista de UFVs',
            'read_ufvs'   => 'Ver detalles de una UFV',
            'add_ufvs'    => 'Agregar nuevas UFVs',
            'delete_ufvs' => 'Eliminar UFVs',
        ];
        foreach ($permissionsUfv as $key => $description) {
            Permission::firstOrCreate([
                'key'            => $key,
                'keyDescription' => $description,
                'table_name'     => 'ufvs',
                'tableDescription'=> 'UFVs'
            ]);
        }

        // Reportes
        $permissionsReportes = [
            'browse_reportes' => 'Acceder al módulo de reportes',
        ];
        foreach ($permissionsReportes as $key => $description) {
            Permission::firstOrCreate([
                'key'            => $key,
                'keyDescription' => $description,
                'table_name'     => 'reportes',
                'tableDescription'=> 'Reportes'
            ]);
        }

        // Permiso para el Wizard de trámites
        Permission::firstOrCreate([
            'key'        => 'browse_tramites_wizard',
            'keyDescription'=>'Acceder al asistente de creación de trámites',
            'table_name' => 'tramites',
        ]);
    }
}
