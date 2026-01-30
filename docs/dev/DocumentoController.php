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

        $documentos = $tramite->documentos()
            ->with(['creador'])
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('admin.tramites.documentos.list', compact('documentos', 'tramite'));
    }

    public function create(Tramite $tramite)
    {
        $this->authorize('create', Documento::class);
        // Inicializar objeto vacío para evitar errores en la vista
        $item = new Documento();
        return view('admin.tramites.documentos.create', compact('tramite', 'item'));
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

            // Versionamiento automático si tiene tipo
            if ($request->tipo) {
                // Marcar anteriores del mismo tipo como no vigentes
                Documento::where('tramite_id', $tramite->id)
                    ->where('tipo', $request->tipo)
                    ->update(['vigente' => false]);

                // Calcular nueva versión
                $maxVersion = Documento::where('tramite_id', $tramite->id)
                    ->where('tipo', $request->tipo)
                    ->max('version');

                $version = $maxVersion ? $maxVersion + 1 : 1;
            }

            Documento::create([
                'tramite_id' => $tramite->id,
                'descripcion' => $request->descripcion,
                'archivo_path' => $path,
                'tipo' => $request->tipo,
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
}
