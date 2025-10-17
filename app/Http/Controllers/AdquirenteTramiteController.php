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

        $data = AdquirenteTramite::with(['persona', 'parentesco'])
            ->where('tramite_id', $tramite->id)
            ->when($search, fn($q) => $q->whereHas('persona', fn($sq) => $sq->where('ci', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('paternal_surname', 'like', "%{$search}%")))
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

        $personas = Person::where('status', 1)
            ->where('estado_persona', 'Activo')
            ->whereDoesntHave('adquirentesTramite', fn($q) => $q->where('tramite_id', $tramite->id))
            ->orderBy('first_name')
            ->orderBy('paternal_surname')
            ->get();
        // dd($personas);
        $parentescos = Parentesco::orderBy('nombre')->get();

        return view('admin.tramites.adquirentes.create', compact('tramite', 'personas', 'parentescos'));
    }

    public function store(StoreAdquirenteTramiteRequest $request, Tramite $tramite)
    {
        $this->authorize('create', AdquirenteTramite::class);

        DB::beginTransaction();
        try {
            $tasa = Tasa::where('departamento_id', $tramite->inmueble->municipio->provincia->departamento_id)
                ->where('parentesco_id', $request->parentesco_id)
                ->where('tipo_transmision_id', $tramite->tipo_transmision_id)
                ->whereDate('vigente_desde', '<=', $tramite->fecha_presentacion)
                ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $tramite->fecha_presentacion))
                ->value('tasa') ?? 0;

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
