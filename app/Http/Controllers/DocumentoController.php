<?php
// app/Http/Controllers/DocumentoController.php
namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Documento;
use App\Models\Person;
use App\Http\Requests\StoreDocumentoRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------- LISTADO (AJAX) ---------- */
    public function index(Tramite $tramite)
    {
        $this->authorize('viewAny', Documento::class);
        return view('admin.tramites.documentos.browse', compact('tramite'));
    }

    public function list(Tramite $tramite)
    {
        $this->authorize('viewAny', Documento::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Documento::with(['persona'])
            ->where('tramite_id', $tramite->id)
            ->when($search, fn($q) => $q->where('tipo_doc', 'like', "%{$search}%"))
            ->orderBy('version', 'desc')
            ->orderBy('id')
            ->paginate($paginate);

        return view('admin.tramites.documentos.list', compact('tramite', 'data'));
    }

    /* ---------- LECTURA ---------- */
    public function show(Tramite $tramite, Documento $item)
    {
        $this->authorize('view', $item);
        return view('admin.tramites.documentos.read', compact('tramite', 'item'));
    }

    /* ---------- ALTA ---------- */
    public function create(Tramite $tramite)
    {
        $this->authorize('create', Documento::class);

        $tipos = ['Escritura', 'Testamento', 'Partida', 'CI', 'Avaluo', 'Poder', 'Otro'];
        $personas = Person::where('status', 1)
            ->where('estado_persona', 'Activo')
            ->orderBy('first_name')
            ->orderBy('paternal_surname')
            ->get();

        // ✅ ESTA LÍNEA ES OBLIGATORIA
        $item = new Documento();

        return view('admin.tramites.documentos.edit-add', compact('tramite', 'tipos', 'personas', 'item'));
    }

    public function store(StoreDocumentoRequest $request, Tramite $tramite)
    {
        $this->authorize('create', Documento::class);

        DB::beginTransaction();
        try {
            // Subir archivo
            $path = $request->file('archivo')->store("tramites/{$tramite->id}/documentos", 'public');

            // Calcular hash SHA-256
            $hash = hash_file('sha256', Storage::disk('public')->path($path));

            // Marcar anteriores del mismo tipo como no vigentes
            Documento::where('tramite_id', $tramite->id)
                ->where('tipo_doc', $request->tipo_doc)
                ->update(['vigente' => false]);

            // Obtener siguiente versión
            $version = Documento::where('tramite_id', $tramite->id)
                ->where('tipo_doc', $request->tipo_doc)
                ->max('version') + 1;

            Documento::create([
                'tramite_id' => $tramite->id,
                'tipo_doc' => $request->tipo_doc,
                'file_path' => $path,
                'hash_sha256' => $hash,
                'person_id' => $request->person_id,
                'vigente' => true,
                'version' => $version,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('admin.tramites.documentos.index', $tramite)
                ->with(['message' => 'Documento guardado (v.' . $version . ').', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            if (isset($path)) Storage::disk('public')->delete($path);
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ---------- BORRADO (solo marca no vigente + soft) ---------- */
    public function destroy(Tramite $tramite, Documento $item)
    {
        $this->authorize('delete', $item);

        if ($item->tramite_id !== $tramite->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            // Si es la versión actual, marcar como no vigente
            $item->update(['vigente' => false]);
            DB::commit();

            return redirect()->route('admin.tramites.documentos.index', $tramite)
                ->with(['message' => 'Documento marcado como no vigente.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }
}
