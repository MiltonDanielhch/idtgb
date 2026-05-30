<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;

class PermissionRoleTableSeeder extends Seeder
{
    /**
     * Auto generated seed file.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('permission_role')->delete();

        // Root
        $role = Role::where('name', 'admin')->firstOrFail();
        $permissions = Permission::all();
        $role->permissions()->sync($permissions->pluck('id')->all());



        $role = Role::where('name', 'administrador')->firstOrFail();
        $permissions = Permission::whereRaw('table_name = "admin" or
                                            `key` = "add_egressdonor" or


                                            table_name = "people" or
                                            table_name = "roles" or
                                            table_name = "users" or
                                            table_name = "settings" or

                                            `key` = "browse_clear-cache"')->get();
        $role->permissions()->sync($permissions->pluck('id')->all());



        $role = Role::where('name', 'tecnico')->firstOrFail();
        $permissions = Permission::whereRaw('table_name = "admin" or
                                            table_name = "people" or
                                            table_name = "parentescos" or
                                            table_name = "exenciones" or
                                            table_name = "feriados" or
                                            table_name = "tipos-transmision" or
                                            table_name = "tipos-inmueble" or
                                            table_name = "tasas" or
                                            table_name = "inmuebles" or
                                            table_name = "avaluos" or
                                            table_name = "tramites" or
                                            table_name = "pagos" or
                                            table_name = "documentos" or
                                            table_name = "ufvs" or
                                            table_name = "reportes" or
                                            `key` = "browse_clear-cache"')->get();
        $role->permissions()->sync($permissions->pluck('id')->all());
    }
}
