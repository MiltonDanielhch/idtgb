<?php

namespace App\Http\Controllers;

use App\Models\Avaluo;
use App\Models\Inmueble;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvaluoController extends Controller
{
    public function index()
    {
        $avaluos = Avaluo::with(['inmueble', 'perito'])
                         ->orderBy('fecha_avaluo', 'desc')
                         ->paginate(20);
        return view('admin.avaluos.index', compact('avaluos'));
    }

    public function create()
    {
        $inmuebles = Inmueble::orderBy('catastro')->get();
        $peritos = Person::where('person_type', 'Natural')->orderBy('first_name')->orderBy('paternal_surname')->get();
        return view('admin.avaluos.create', compact('inmuebles', 'peritos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'inmueble_id'    => 'required|exists:inmuebles,id',
            'tipo_avaluo'    => 'required|in:Fiscal,Comercial,Pericial',
            'fecha_avaluo'   => 'required|date',
            'valor'          => 'required|numeric|min:0',
            'perito_id'      => 'nullable|exists:people,id',
            'documento'      => 'nullable|file|mimes:pdf,jpg,png|max:5120', // 5 MB
            'estado'         => 'in:Vigente,Caducado',
        ]);

        $path = null;
        if ($request->hasFile('documento')) {
            $path = $request->file('documento')->store('avaluos', 'public');
        }

        Avaluo::create([
            'inmueble_id'     => $request->inmueble_id,
            'tipo_avaluo'     => $request->tipo_avaluo,
            'fecha_avaluo'    => $request->fecha_avaluo,
            'valor'           => $request->valor,
            'perito_id'       => $request->perito_id,
            'documento_path'  => $path,
            'estado'          => $request->estado ?? 'Vigente',
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
        ]);

        return redirect()->route('admin.avaluos.index')
            ->with(['message' => 'Avalúo creado.', 'alert-type' => 'success']);
    }

    public function edit(Avaluo $avaluo)
    {
        $inmuebles = Inmueble::orderBy('catastro')->get();
        $peritos = Person::where('person_type', 'Natural')->orderBy('first_name')->orderBy('paternal_surname')->get();
        return view('admin.avaluos.edit', compact('avaluo', 'inmuebles', 'peritos'));
    }

    public function update(Request $request, Avaluo $avaluo)
    {
        $request->validate([
            'inmueble_id'    => 'required|exists:inmuebles,id',
            'tipo_avaluo'    => 'required|in:Fiscal,Comercial,Pericial',
            'fecha_avaluo'   => 'required|date',
            'valor'          => 'required|numeric|min:0',
            'perito_id'      => 'nullable|exists:people,id',
            'documento'      => 'nullable|file|mimes:pdf,jpg,png|max:5120',
            'estado'         => 'in:Vigente,Caducado',
        ]);

        $path = $avaluo->documento_path;
        if ($request->hasFile('documento')) {
            if ($path) Storage::disk('public')->delete($path);
            $path = $request->file('documento')->store('avaluos', 'public');
        }

        $avaluo->update([
            'inmueble_id'     => $request->inmueble_id,
            'tipo_avaluo'     => $request->tipo_avaluo,
            'fecha_avaluo'    => $request->fecha_avaluo,
            'valor'           => $request->valor,
            'perito_id'       => $request->perito_id,
            'documento_path'  => $path,
            'estado'          => $request->estado ?? 'Vigente',
            'updated_by'      => auth()->id(),
        ]);

        return redirect()->route('admin.avaluos.index')
            ->with(['message' => 'Avalúo actualizado.', 'alert-type' => 'success']);
    }

    public function destroy(Avaluo $avaluo)
    {
        if ($avaluo->documento_path) {
            Storage::disk('public')->delete($avaluo->documento_path);
        }
        $avaluo->delete();
        return redirect()->route('admin.avaluos.index')
            ->with(['message' => 'Avalúo eliminado.', 'alert-type' => 'success']);
    }

    public function download(Avaluo $avaluo)
    {
        if (!$avaluo->documento_path) abort(404);
        return Storage::disk('public')->download($avaluo->documento_path);
    }
}
