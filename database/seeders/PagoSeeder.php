<?php

namespace Database\Seeders;

use App\Models\Pago;
use App\Models\Tramite;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PagoSeeder extends Seeder
{
    public function run(): void
    {
        $tramite = Tramite::first()->id;
        $nroOp = 'SISTEC-1234567890123456789';
        Pago::create([
            'tramite_id'     => $tramite,
            'fecha_pago'     => now(),
            'monto'          => 5000.00,
            'codigo_barras'  => '1234567890123456789012345',
            'nro_operacion'  => substr('SISTEC-1234567890123456789', 0, 25), // ← 25
            'banco'          => 'Banco Unión',
            'estado'         => 'Aplicado',
            'conciliado_el'  => now(),
        ]);
    }
}
