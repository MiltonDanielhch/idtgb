<?php

namespace Database\Seeders;

use App\Models\{AdquirenteTramite, Avaluo, Departamento, DisponenteTramite, Documento,
              Exencion, Inmueble, Municipio, Parentesco, Person, Provincia, Tasa,
              TipoInmueble, TipoTransmision, Tramite, TramiteExencion, Ufv, User};
use App\Services\CodigoBarrasService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdtgbCompletoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->truncateAll();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Maestros
        $departamento = $this->departamentos();
        $this->provincias($departamento);
        $this->municipios();
        $this->parentescos();
        $this->tiposTransmision();
        $this->tiposInmueble();
        $this->tasas($departamento);
        $this->exenciones();
        $this->ufv();

        // 2. Personas
        $causante = $this->personaCausante();
        $adquirente = $this->personaAdquirente();

        // 3. Inmueble + Avalúo
        $inmueble = $this->inmuebleConAvaluo();

        // 4. Trámite completo
        $this->tramiteCompleto($inmueble, $causante, $adquirente);

        $this->command->info('✅ IDTGB-Beni 2025: datos de prueba cargados.');
        $this->command->info('   - Departamentos: '.Departamento::count());
        $this->command->info('   - Trámites: '.Tramite::count());
        $this->command->info('   - Inmuebles: '.Inmueble::count());
        $this->command->info('   - Pagos: '.DB::table('pagos')->count());
    }

    /* ------------------------------------------------------------- */
    /* ----------------------  UTILIDADES  ------------------------- */
    /* ------------------------------------------------------------- */
    private function truncateAll(): void
    {
        $tablas = [
            'departamentos','provincias','municipios','parentescos','tipos_transmision',
            'tipos_inmueble','tasas','exenciones','people','inmuebles','avaluos','ufvs',
            'tramites','adquirentes_tramite','disponentes_tramite','tramite_exenciones',
            'pagos','documentos','tramite_inmuebles',
        ];
        foreach ($tablas as $t) DB::table($t)->truncate();
    }

    /* ------------------------------------------------------------- */
    /* ------------------------  MAESTROS  ------------------------- */
    /* ------------------------------------------------------------- */
    private function departamentos(): Departamento
    {
        return Departamento::firstOrCreate(['codigo' => 'BE'], ['nombre' => 'Beni']);
    }

    private function provincias(Departamento $dep): void
    {
        foreach (['Moxos','Yacuma','Cercado'] as $p) {
            $dep->provincias()->firstOrCreate(['nombre' => $p]);
        }
    }

    private function municipios(): void
    {
        $prov = Provincia::where('nombre','Cercado')->first();
        foreach (['Trinidad','San Javier','San Pedro'] as $m) {
            $prov->municipios()->firstOrCreate(['nombre' => $m]);
        }
    }

    private function parentescos(): void
    {
        foreach (['Cónyuge','Hijo/a','Padre/Madre','Hermano/a','Sin parentesco'] as $p) {
            Parentesco::firstOrCreate(['nombre' => $p]);
        }
    }

    private function tiposTransmision(): void
    {
        foreach (['Herencia','Donación','Legado'] as $t) {
            TipoTransmision::firstOrCreate(['nombre' => $t]);
        }
    }

    private function tiposInmueble(): void
    {
        foreach (['Urbana','Rural','Mixta'] as $t) {
            TipoInmueble::firstOrCreate(['nombre' => $t]);
        }
    }

    private function tasas(Departamento $dep): void
    {
        $herencia = TipoTransmision::where('nombre','Herencia')->first();
        $map = [1 => 1.00, 2 => 1.00, 3 => 1.00, 4 => 5.00, 5 => 20.00];
        foreach ($map as $pid => $tasa) {
            Tasa::firstOrCreate([
                'departamento_id'      => $dep->id,
                'parentesco_id'        => $pid,
                'tipo_transmision_id'  => $herencia->id,
                'vigente_desde'        => '2025-01-01',
            ], ['tasa' => $tasa]);
        }
    }

    private function exenciones(): void
    {
        Exencion::firstOrCreate(
            ['nombre' => 'Vivienda única familiar'],
            [
                'descripcion'   => 'Exención 100 % sobre vivienda única (Beni 2025)',
                'tipo'          => 'porcentaje',
                'valor'         => 100.00,
                'vigente_desde' => '2025-01-01',
            ]
        );
    }

    private function ufv(): void
    {
        Ufv::firstOrCreate(['fecha' => '2025-01-01'], ['valor' => 2.30945]);
        Ufv::firstOrCreate(['fecha' => '2025-02-01'], ['valor' => 2.31567]);
    }

    /* ------------------------------------------------------------- */
    /* -----------------------  PERSONAS  -------------------------- */
    /* ------------------------------------------------------------- */
    private function personaCausante(): Person
    {
        return Person::firstOrCreate(
            ['ci' => '1234567', 'ci_complemento' => 'LP'],
            [
                'person_type'   => 'Natural',
                'tipo_doc'      => 'CI',
                'first_name'    => 'Juan',
                'paternal_surname' => 'Pérez',
                'estado_persona'=> 'Fallecido',
                'birth_date'    => '1950-01-01',
            ]
        );
    }

    private function personaAdquirente(): Person
    {
        return Person::firstOrCreate(
            ['ci' => '7654321'],
            [
                'person_type'   => 'Natural',
                'tipo_doc'      => 'CI',
                'first_name'    => 'Carlos',
                'paternal_surname' => 'Pérez',
                'estado_persona'=> 'Activo',
                'birth_date'    => '1980-05-15',
                'email'         => 'carlos@mail.com',
            ]
        );
    }

    /* ------------------------------------------------------------- */
    /* ----------------------  INMUEBLE  --------------------------- */
    /* ------------------------------------------------------------- */
    private function inmuebleConAvaluo(): Inmueble
    {
        $inm = Inmueble::firstOrCreate(
            ['catastro' => '12-3456-01-0101'],
            [
                'tipo_inmueble_id'           => TipoInmueble::where('nombre','Urbana')->first()->id,
                'municipio_id'               => Municipio::where('nombre','Trinidad')->first()->id,
                'direccion'                  => 'Calle Avaroa #123',
                'superficie_m2'              => 250.00,
                'valor_catastral'            => 500000.00,
                'matricula_rr'               => 'RR-123456-2025',
                'es_vivienda_unica_familiar' => true,
                'estado_inmueble'            => 'Activo',
            ]
        );

        Avaluo::firstOrCreate(
            ['inmueble_id' => $inm->id, 'tipo_avaluo' => 'Fiscal'],
            [
                'fecha_avaluo' => now()->subDays(5),
                'valor'        => 500000.00,
                'estado'       => 'Vigente',
            ]
        );

        return $inm;
    }

    /* ------------------------------------------------------------- */
    /* -----------------------  TRÁMITE  --------------------------- */
    /* ------------------------------------------------------------- */
    private function tramiteCompleto(Inmueble $inm, Person $causante, Person $adquirente): void
    {
        $base = max($inm->valor_catastra    l, $inm->avaluos()->first()->valor); // 500000
        $tasa = Tasa::where('parentesco_id', Parentesco::where('nombre','Hijo/a')->first()->id)
                    ->where('departamento_id', Departamento::where('codigo','BE')->first()->id)
                    ->value('tasa'); // 1 %
        $idtgb = $base * ($tasa / 100);                                     // 5000
        $exencion = Exencion::where('nombre','Vivienda única familiar')->first();
        $montoExencion = $exencion && $inm->es_vivienda_unica_familiar ? $idtgb : 0;
        $final = $idtgb - $montoExencion;                                   // 0

        $tramite = Tramite::create([
            'nro_tramite'        => 'BE-2025-0001',
            'fecha_presentacion' => now(),
            'tipo_transmision_id'=> TipoTransmision::where('nombre','Herencia')->first()->id,
            'valor_declarado'    => $base,
            'base_imponible'     => $base,
            'total_idtgb'        => $idtgb,
            'recargo_mora'       => 0,
            'monto_final'        => $final,
            'ufv_aplicada'       => Ufv::whereDate('fecha', today())->value('valor') ?? 2.30945,
            'estado'             => 'Borrador',
            'fecha_transmision'  => now()->subMonths(2),
            'fecha_vencimiento'  => now()->addDays(10),
            'observaciones'      => 'Trámite de prueba IDTGB-Beni',
            'user_id'            => User::first()->id ?? 1,
            'created_by'         => User::first()->id ?? 1,
            'updated_by'         => User::first()->id ?? 1,
        ]);

        // Pivote inmuebles
        $tramite->inmuebles()->attach($inm->id);

        // Adquirente
        AdquirenteTramite::create([
            'tramite_id'        => $tramite->id,
            'person_id'        => $adquirente->id,
            'parentesco_id'     => Parentesco::where('nombre','Hijo/a')->first()->id,
            'tasa_aplicada'     => $tasa,
            'porcentaje'        => 100.00,
            'idtgb_proporcional'=> $idtgb,
        ]);

        // Disponente
        DisponenteTramite::create([
            'tramite_id'         => $tramite->id,
            'person_id'         => $causante->id,
            'tipo'               => 'Causante',
            'fecha_fallecimiento'=> now()->subMonths(2),
            'es_discapacitado'   => false,
        ]);

        // Exención
        TramiteExencion::create([
            'tramite_id'   => $tramite->id,
            'exencion_id'  => $exencion->id,
            'monto_aplicado'=> $montoExencion,
        ]);

        // Pago con código de barras
        // $codigo = app(CodigoBarrasService::class)
        //           ->generar('BE','01',$tramite->id);
        $codigo = sprintf('BE01%013d9', $tramite->id);
        DB::table('pagos')->insert([
            'tramite_id'    => $tramite->id,
            'fecha_pago'    => now(),
            'monto'         => 0,
            'codigo_barras' => $codigo,
            'nro_operacion' => 'SISTEC-'.$codigo,
            'banco'         => 'Banco Unión',
            'estado'        => 'Pendiente',
            'created_by'    => User::first()->id ?? 1,
            'updated_by'    => User::first()->id ?? 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Documentos
        $docs = [
            ['tipo'=>'CI',       'file'=>'ci_heredero.pdf'],
            ['tipo'=>'Escritura','file'=>'escritura_herencia.pdf'],
            ['tipo'=>'Avaluo',   'file'=>'avaluo_fiscal.pdf'],
        ];
        foreach ($docs as $d) {
            Documento::create([
                'tramite_id' => $tramite->id,
                'tipo_doc'   => $d['tipo'],
                'file_path'  => 'documentos/'.$d['file'],
                'hash_sha256'=> hash('sha256',$d['file']),
                'person_id' => $adquirente->id,
                'vigente'    => true,
                'version'    => 1,
            ]);
        }
    }
}
