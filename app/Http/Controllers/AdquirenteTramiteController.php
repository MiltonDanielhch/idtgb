<?php
// app/Http/Controllers/AdquirenteTramiteController.php
namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Person;
use App\Models\Parentesco;
use App\Models\AdquirenteTramite;
use App\Models\Tasa;
use App\Http\Requests\StoreAdquirenteTramiteRequest;
use App\Services\IdtgbCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdquirenteTramiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------- LISTADO (AJAX) ---------- */
    public function index(Tramite $tramite)
    {
        $this->authorize('viewAny', AdquirenteTramite::class);
        return view('admin.tramites.adquirentes.browse', compact('tramite'));
    }

    public function list(Tramite $tramite)
    {
        $this->authorize('viewAny', AdquirenteTramite::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = AdquirenteTramite::with(['person', 'parentesco'])
            ->where('tramite_id', $tramite->id)
            ->when($search, fn($q) => $q->whereHas('person', fn($sq) => $sq->where('ci', 'like', "%{$search}%")->orWhere('nombre_completo', 'like', "%{$search}%")))
            ->orderBy('id')
            ->paginate($paginate);

        return view('admin.tramites.adquirentes.list', compact('tramite', 'data'));
    }

    /* ---------- LECTURA ---------- */
    public function show(Tramite $tramite, AdquirenteTramite $item)
    {
        $this->authorize('view', $item);
        return view('admin.tramites.adquirentes.read', compact('tramite', 'item'));
    }

    /* ---------- ALTA ---------- */
    public function create(Tramite $tramite)
    {
        $this->authorize('create', AdquirenteTramite::class);

        $personas = Person::whereDoesntHave('adquirentesTramite', fn($q) => $q->where('tramite_id', $tramite->id))
            ->orderBy('nombre_completo')
            ->get();
        // dd($personas);
        $parentescos = Parentesco::orderBy('nombre')->get();

        // Es necesario crear una instancia vacía para que la vista 'edit-add'
        // funcione correctamente en modo 'creación'.
        $item = new AdquirenteTramite();

        return view('admin.tramites.adquirentes.edit-add', compact('tramite', 'personas', 'parentescos', 'item'));
    }

    public function store(StoreAdquirenteTramiteRequest $request, Tramite $tramite)
    {
        $this->authorize('create', AdquirenteTramite::class);

        DB::beginTransaction();
        try {
            $departamentoId = $tramite->inmueble->municipio->provincia->departamento_id ?? null;

            if (!$departamentoId) {
                throw new \Exception("El trámite no tiene un departamento asignado. No se puede calcular la tasa.");
            }

            $tasaModel = Tasa::findApplicableRate(
                $departamentoId,
                $request->parentesco_id,
                $tramite->tipo_transmision_id,
                $tramite->fecha_presentacion
            );

            $tasa = $tasaModel ? $tasaModel->tasa : 0;

            $path = null;
            if ($request->hasFile('documento_sustento_exencion')) {
                $path = $request->file('documento_sustento_exencion')->store("tramites/{$tramite->id}/adquirentes", 'public');
            }

            AdquirenteTramite::create([
                'tramite_id' => $tramite->id,
                'person_id' => $request->person_id,
                'parentesco_id' => $request->parentesco_id,
                'tasa_aplicada' => $tasa,
                'porcentaje' => $request->porcentaje,
                'idtgb_proporcional' => 0, // se calculará después
                'es_beneficiario_exencion' => $request->boolean('es_beneficiario_exencion'),
                'documento_sustento_exencion' => $path,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            app(IdtgbCalculator::class)->calcular($tramite);

            DB::commit();

            return redirect()->route('admin.tramites.adquirentes.index', $tramite)
                ->with(['message' => 'Adquirente agregado.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ---------- BORRADO ---------- */
    public function destroy(Tramite $tramite, AdquirenteTramite $item)
    {
        $this->authorize('delete', $item);

        if ($item->tramite_id !== $tramite->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            if ($item->documento_sustento_exencion) {
                Storage::disk('public')->delete($item->documento_sustento_exencion);
            }
            $item->delete();
            app(IdtgbCalculator::class)->calcular($tramite);
            DB::commit();

            return redirect()->route('admin.tramites.adquirentes.index', $tramite)
                ->with(['message' => 'Adquirente quitado.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }
}
