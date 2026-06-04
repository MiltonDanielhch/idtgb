<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder para despliegue a PRODUCCIÓN
 *
 * Este seeder contiene SOLO los datos esenciales para el funcionamiento
 * del sistema IDTGB en producción. NO incluye datos de prueba.
 *
 * Para desarrollo/local, usa IdtgbMaestrosSeeder completo.
 *
 * @see docs/produccion/GUIA_DESPLIEGUE_PRODUCCION.md
 */
class IdtgbMaestrosProduccionSeeder extends Seeder
{
    /**
     * Ejecuta los seeders esenciales para producción.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            // ============================================================
            // 1. SISTEMA BASE VOYAGER (OBLIGATORIO)
            // ============================================================
            // Crea: usuarios base, roles, permisos, tipos de datos BREAD
            VoyagerDatabaseSeeder::class,

            // ============================================================
            // 2. GEOGRAFÍA DE BOLIVIA (OBLIGATORIO)
            // ============================================================
            // Crea: departamentos, provincias, municipios
            DepartamentoSeeder::class,
            ProvinciaSeeder::class,
            MunicipioSeeder::class,

            // ============================================================
            // 3. MAESTROS DEL SISTEMA (OBLIGATORIO)
            // ============================================================
            // Estos son los datos que definen la lógica de negocio
            ParentescoSeeder::class,      // Parentescos y sus tasas (1%, 10%, 20%)
            TipoTransmisionSeeder::class, // Tipos: Herencia, Donación, etc.
            TipoInmuebleSeeder::class,    // Tipos: Casa, Terreno, etc.
            TasaSeeder::class,            // Tasas impositivas vigentes por parentesco
            ExencionSeeder::class,        // Exenciones legales aplicables

            // ============================================================
            // 4. FERIADOS (OBLIGATORIO)
            // ============================================================
            // Necesario para el cálculo de días hábiles en donaciones
            // (DiasHabilesService excluye sábados, domingos y feriados)
            FeriadoSeeder::class,         // Feriados nacionales + Beni 2026

            // ============================================================
            // 5. MENÚS Y NAVEGACIÓN (OBLIGATORIO)
            // ============================================================
            // Configura el menú de administración en Voyager
            IdtgbMenuAppendSeeder::class,
            UsersTableSeeder::class,
            // ============================================================
            // 6. UFV - UNIDADES DE FOMENTO DE VIVIENDA (RECOMENDADO)
            // ============================================================
            // Opciones para cargar UFV:

            // Opción A: Datos históricos desde seeder (más rápido)
            // UfvSeeder::class,

            // Opción B: Datos actualizados desde API del BCB (más lento, más preciso)
            // UfvApiSeeder::class,

            // Opción C: Cargar manualmente después mediante comando
            // php artisan ufv:import archivo.csv
        ]);

        // ================================================================
        // NOTA IMPORTANTE SOBRE USUARIOS
        // ================================================================
        // El usuario administrador se crea con:
        // Email: admin@example.com (DEBES CAMBIARLO)
        // Password: password (DEBES CAMBIARLO INMEDIATAMENTE)
        //
        // Para cambiarlo:
        // 1. Ve al panel de administración
        // 2. Menú: Usuarios → Editar admin
        // 3. Cambiar email a uno institucional
        // 4. Cambiar contraseña por una segura (mínimo 12 caracteres)
        //
        // O ejecutar:
        // php artisan tinker
        // $user = App\Models\User::where('email', 'admin@example.com')->first();
        // $user->email = 'admin@tuinstitucion.gob.bo';
        // $user->password = bcrypt('NuevaPasswordSegura123!');
        // $user->save();
        // ================================================================

        $this->command->info('✅ Datos de producción cargados exitosamente');
        $this->command->warn('⚠️  IMPORTANTE: Cambiar email y password del usuario admin inmediatamente');
        $this->command->warn('⚠️  IMPORTANTE: Configurar UFV antes de usar el sistema');
    }
}
