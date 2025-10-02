<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('users')->delete();

        \DB::table('users')->insert(array (
            0 =>
            array (
                'id' => 1,
                'role_id' => 1,
                'name' => 'Admin',
                'email' => 'milton@admin.com',
                'avatar' => 'users/default.png',
                'email_verified_at' => NULL,
                'password' => '$2y$10$ILLZfhsbwinK3235ceVa7O0mj3M5fr33wb3z28aDqiBWLwBfSUzYy',
                'remember_token' => 's50ePpNF7KmjeTDifOaandO0P5mYpF8IAlmuMCGcIk3mjVn0OCITiXtb6xxj',
                'settings' => '{"locale":"es"}',
                'created_at' => '2024-10-18 14:28:45',
                'updated_at' => '2024-10-18 14:33:30',
            ),
            1 =>
            array (
                'id' => 2,
                'role_id' => 2,
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'avatar' => 'users/default.png',
                'email_verified_at' => NULL,
                'password' => '$2y$10$ILLZfhsbwinK3235ceVa7O0mj3M5fr33wb3z28aDqiBWLwBfSUzYy',
                'remember_token' => 's50ePpNF7KmjeTDifOaandO0P5mYpF8IAlmuMCGcIk3mjVn0OCITiXtb6xxj',
                'settings' => '{"locale":"es"}',
                'created_at' => '2024-10-18 14:28:45',
                'updated_at' => '2024-10-18 14:33:30',
            ),
        ));


    }
}
