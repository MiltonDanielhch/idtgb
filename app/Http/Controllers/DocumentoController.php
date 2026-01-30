<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Tramite;
use App\Http\Requests\StoreDocumentoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentoController extends Controller
{
    public function index(Tramite $tramite)
    {
        $this->authorize('viewAny', Documento::class);
        return view('admin.tramites.documentos.browse', compact('tramite'));
    }

    public function list(Tramite $tramite)
    {
        $this->authorize('viewAny', Documento::class);

        $data = $tramite->documentos()
            ->with(['persona'])
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('admin.tramites.documentos.list', compact('data', 'tramite'));
    }

    public function create(Tramite $tramite)
    {
        $this->authorize('create', Documento::class);

        $tipos = [
            'Cédula de Identidad',
            'NIT',
            'Testimonio',
            'Folio Real',
            'Minuta',
            'Plano',
            'Pago Impuestos',
            'Certificado',
            'Otro'
        ];

        // Obtener personas asociadas al trámite (Adquirentes y Disponentes)
        $tramite->load(['adquirentes.person', 'disponentes.person']);
        $personas = $tramite->adquirentes->pluck('person')
            ->merge($tramite->disponentes->pluck('person'))
            ->filter()
            ->unique('id');

        // Inicializar objeto vacío para evitar errores en la vista
        $item = new Documento();
        return view('admin.tramites.documentos.edit-add', compact('tramite', 'item', 'tipos', 'personas'));
    }

    public function store(StoreDocumentoRequest $request, Tramite $tramite)
    {
        $this->authorize('create', Documento::class);

        DB::beginTransaction();
        try {

            $file = $request->file('archivo');
            // Normalizar nombre de archivo
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs('documentos/' . $tramite->id, $fileName, 'public');

            // Calcular Hash SHA-256 para integridad
            $hash = hash_file('sha256', Storage::disk('public')->path($path));

            $version = 1;
            $tipoDoc = $request->input('tipo_doc');

            // Versionamiento automático si tiene tipo
            if ($tipoDoc) {
                // Marcar anteriores del mismo tipo como no vigentes
                Documento::where('tramite_id', $tramite->id)
                    ->where('tipo_doc', $tipoDoc)
                    ->update(['vigente' => false]);

                // Calcular nueva versión
                $maxVersion = Documento::where('tramite_id', $tramite->id)
                    ->where('tipo_doc', $tipoDoc)
                    ->max('version');

                $version = $maxVersion ? $maxVersion + 1 : 1;
            }

            Documento::create([
                'tramite_id' => $tramite->id,
                'person_id' => $request->input('person_id'),
                'descripcion' => $request->descripcion,
                'archivo_path' => $path,
                'tipo_doc' => $tipoDoc,
                'hash_sha256' => $hash,
                'version' => $version,
                'vigente' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('admin.tramites.documentos.index', $tramite)
                ->with(['message' => 'Documento subido exitosamente.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            // Limpieza de archivo huérfano en caso de error
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            Log::error('Error al subir documento: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return back()->withInput()
                ->with(['message' => 'Error al subir documento: ' . $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    public function show(Tramite $tramite, Documento $item)
    {
        $this->authorize('view', $item);
        return view('admin.tramites.documentos.read', compact('tramite', 'item'));
    }

    public function destroy(Tramite $tramite, Documento $item)
    {
        $this->authorize('delete', $item);

        // Validación de pertenencia
        if ($item->tramite_id !== $tramite->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            // Marcado lógico como no vigente y soft delete
            $item->update([
                'vigente' => false,
                'updated_by' => auth()->id()
            ]);

            $item->delete();

            DB::commit();

            return redirect()->route('admin.tramites.documentos.index', $tramite)
                ->with(['message' => 'Documento eliminado correctamente.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with(['message' => 'Error al eliminar: ' . $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    public function download(Tramite $tramite, Documento $item)
    {
        $this->authorize('view', $item);

        if ($item->archivo_path && Storage::disk('public')->exists($item->archivo_path)) {
            return Storage::disk('public')->download($item->archivo_path);
        }

        return back()->with(['message' => 'El archivo no existe o fue eliminado.', 'alert-type' => 'error']);
    }
}
