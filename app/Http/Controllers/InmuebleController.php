<?php

namespace App\Http\Controllers;

use App\Models\Inmueble;
use App\Models\TipoInmueble;
use App\Models\Municipio;
use Illuminate\Http\Request;

class InmuebleController extends Controller
{
    public function index()
    {
        $inmuebles = Inmueble::with(['tipoInmueble', 'municipio'])
                             ->orderBy('catastro')
                             ->paginate(20);
        return view('admin.inmuebles.index', compact('inmuebles'));
    }

    public function create()
    {
        $tipos = TipoInmueble::orderBy('nombre')->get();
        $municipios = Municipio::with('provincia.departamento')->orderBy('nombre')->get();
        return view('admin.inmuebles.create', compact('tipos', 'municipios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'complemento'                => 'nullable|string|max:3',
            'catastro'                   => 'required|string|max:15|unique:inmuebles',
            'tipo_inmueble_id'           => 'required|exists:tipos_inmueble,id',
            'municipio_id'               => 'nullable|exists:municipios,id',
            'barrio_comunidad'           => 'nullable|string|max:100',
            'direccion'                  => 'nullable|string|max:200',
            'superficie_m2'              => 'nullable|numeric|min:0',
            'valor_catastral'            => 'required|numeric|min:0',
            'matricula_rr'               => 'nullable|string|max:20',
            'es_vivienda_unica_familiar' => 'boolean',
            'estado_inmueble'            => 'in:Activo,Transferido,Baja',
        ]);

        Inmueble::create($request->all());

        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble creado.', 'alert-type' => 'success']);
    }

    public function edit(Inmueble $inmueble)
    {
        $tipos = TipoInmueble::orderBy('nombre')->get();
        $municipios = Municipio::with('provincia.departamento')->orderBy('nombre')->get();
        return view('admin.inmuebles.edit', compact('inmueble', 'tipos', 'municipios'));
    }

    public function update(Request $request, Inmueble $inmueble)
    {
        $request->validate([
            'complemento'                => 'nullable|string|max:3',
            'catastro'                   => 'required|string|max:15|unique:inmuebles,catastro,'.$inmueble->id,
            'tipo_inmueble_id'           => 'required|exists:tipos_inmueble,id',
            'municipio_id'               => 'nullable|exists:municipios,id',
            'barrio_comunidad'           => 'nullable|string|max:100',
            'direccion'                  => 'nullable|string|max:200',
            'superficie_m2'              => 'nullable|numeric|min:0',
            'valor_catastral'            => 'required|numeric|min:0',
            'matricula_rr'               => 'nullable|string|max:20',
            'es_vivienda_unica_familiar' => 'boolean',
            'estado_inmueble'            => 'in:Activo,Transferido,Baja',
        ]);

        $inmueble->update($request->all());

        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble actualizado.', 'alert-type' => 'success']);
    }

    public function destroy(Inmueble $inmueble)
    {
        $inmueble->delete();
        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble eliminado.', 'alert-type' => 'success']);
    }
}
