<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Documento;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class DocumentoController extends Controller
{
    public function index(Tramite $tramite)
    {
        $documentos = $tramite->documentos()->with('persona')->orderBy('created_at', 'desc')->get();
        return view('admin.tramites.documentos.index', compact('tramite', 'documentos'));
    }

    public function create(Tramite $tramite)
    {
        $tipos = ['Escritura', 'Testamento', 'Partida', 'CI', 'Avaluo', 'Poder', 'Otro'];
        $personas = Person::where('person_type', 'Natural')->orderBy('first_name')->orderBy('paternal_surname')->get();
        return view('admin.tramites.documentos.create', compact('tramite', 'tipos', 'personas'));
    }

    public function store(Request $request, Tramite $tramite)
    {
        $request->validate([
            'tipo_doc'                  => 'required|in:Escritura,Testamento,Partida,CI,Avaluo,Poder,Otro',
            'documento'                 => 'required|file|mimes:pdf,jpg,png|max:5120', // 5 MB
            'persona_id'                => 'nullable|exists:people,id',
            'vigente'                   => 'boolean',
            'version'                   => 'nullable|integer|min:1',
        ]);

        // Subir archivo
        $path = $request->file('documento')->store('documentos', 'public');

        // Calcular hash SHA-256
        $hash = hash_file('sha256', Storage::disk('public')->path($path));

        // Obtener última versión si no se especifica
        $version = $request->version ?? 1;

        Documento::create([
            'tramite_id'     => $tramite->id,
            'tipo_doc'       => $request->tipo_doc,
            'file_path'      => $path,
            'hash_sha256'    => $hash,
            'persona_id'     => $request->persona_id,
            'vigente'        => $request->boolean('vigente'),
            'version'        => $version,
        ]);

        return redirect()->route('admin.tramites.documentos.index', $tramite)
            ->with(['message' => 'Documento guardado.', 'alert-type' => 'success']);
    }

    public function destroy(Tramite $tramite, Documento $documento)
    {
        if ($documento->tramite_id !== $tramite->id) {
            abort(404);
        }

        // Borrar archivo físico
        if ($documento->file_path) {
            Storage::disk('public')->delete($documento->file_path);
        }

        $documento->delete();

        return redirect()->route('admin.tramites.documentos.index', $tramite)
            ->with(['message' => 'Documento eliminado.', 'alert-type' => 'success']);
    }

    public function download(Tramite $tramite, Documento $documento)
    {
        if ($documento->tramite_id !== $tramite->id) {
            abort(404);
        }

        if (!$documento->file_path) {
            abort(404);
        }

        return Storage::disk('public')->download($documento->file_path);
    }
}
