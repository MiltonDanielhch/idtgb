<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file.
     */
    public function run()
    {
        $role = Role::firstOrNew(['name' => 'admin']);
        if (!$role->exists) {
            $role->fill([
                'display_name' => __('Admin'),
            ])->save();
        }

        $role = Role::firstOrNew(['name' => 'administrador']);
        if (!$role->exists) {
            $role->fill([
                'display_name' => __('Administrador'),
            ])->save();
        }

        $role = Role::firstOrNew(['name' => 'tecnico']);
        if (!$role->exists) {
            $role->fill([
                'display_name' => __('Técnico'),
            ])->save();
        }



        // $role = Role::firstOrNew(['name' => 'almacen_admin']);
        // if (!$role->exists) {
        //     $role->fill([
        //         'display_name' => __('Almacen - Administrador de todos los Almacenes'),
        //     ])->save();
        // }

        // $role = Role::firstOrNew(['name' => 'almacen_subadmin']);
        // if (!$role->exists) {
        //     $role->fill([
        //         'display_name' => __('Almacen - Responsable de almacen "Report"'),
        //     ])->save();
        // }
    }
}
