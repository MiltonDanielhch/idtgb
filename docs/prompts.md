# Ejemplo CRUD Completo - Inmueble

Este es el mejor ejemplo de CRUD completo del sistema. Sigue todas las mejores prácticas de Laravel incluyendo:

- Form Requests para validación
- Policies para autorización
- Manejo de errores con Log
- Relaciones entre modelos
- Verificación de dependencias
- AJAX para listado
- SoftDeletes
- Paginación dinámica

---

## 1. MODELO: app/Models/Inmueble.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inmueble extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inmuebles';

    protected $fillable = [
        'complemento',
        'catastro',
        'tipo_inmueble_id',
        'municipio_id',
        'barrio_comunidad',
        'direccion',
        'superficie_m2',
        'valor_catastral',
        'matricula_rr',
        'es_vivienda_unica_familiar',
        'estado_inmueble',
        'updated_by',
    ];

    protected $casts = [
        'es_vivienda_unica_familiar' => 'boolean',
        'superficie_m2'              => 'decimal:2',
        'valor_catastral'            => 'decimal:2',
    ];

    public function tramiteInmuebles()
    {
        return $this->hasMany(TramiteInmueble::class);
    }

    public function tipoInmueble()
    {
        return $this->belongsTo(TipoInmueble::class);
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    public function avaluos()
    {
        return $this->hasMany(Avaluo::class);
    }

    public function avaluoVigente()
    {
        return $this->avaluos()->where('estado', 'Vigente')->latest()->first();
    }
}
```

---

## 2. CONTROLADOR: app/Http/Controllers/InmuebleController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Inmueble;
use App\Models\TipoInmueble;
use App\Models\Municipio;
use App\Http\Requests\UpdateInmuebleRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class InmuebleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', Inmueble::class);
        return view('admin.inmuebles.browse');
    }

    public function list()
    {
        $this->authorize('viewAny', Inmueble::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Inmueble::with(['tipoInmueble', 'municipio.provincia.departamento'])
            ->when($search, fn($q) => $q->where('catastro', 'like', "%{$search}%")
                ->orWhere('direccion', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.inmuebles.list', compact('data'));
    }

    public function show(Inmueble $inmueble)
    {
        $this->authorize('view', $inmueble);
        return view('admin.inmuebles.read', compact('inmueble'));
    }

    public function create()
    {
        $this->authorize('create', Inmueble::class);
        return view('admin.inmuebles.edit-add', [
            'inmueble'    => new Inmueble(),
            'tipos'       => TipoInmueble::orderBy('nombre')->get(),
            'municipios'   => Municipio::limit(100)->with('provincia.departamento')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Inmueble::class);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'catastro' => 'required|unique:inmuebles,catastro',
            'direccion' => 'required',
            'tipo_inmueble_id' => 'required',
            'municipio_id' => 'required',
            'valor_catastral' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with(['message' => 'Error de validación: ' . $validator->errors()->first(), 'alert-type' => 'error']);
        }

        try {
            $data = $validator->validated();
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            Inmueble::create($data);

            return redirect()->route('admin.inmuebles.index')
                ->with(['message' => 'Inmueble creado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error al crear inmueble: ' . $e->getMessage());
            return back()->withInput()
                ->with(['message' => 'Ocurrió un error al guardar el inmueble.', 'alert-type' => 'error']);
        }
    }

    public function edit(Inmueble $inmueble)
    {
        $this->authorize('update', $inmueble);
        return view('admin.inmuebles.edit-add', [
            'inmueble'    => $inmueble,
            'tipos'       => TipoInmueble::orderBy('nombre')->get(),
            'municipios'   => Municipio::limit(100)->with('provincia.departamento')->orderBy('nombre')->get(),
        ]);
    }

    public function update(UpdateInmuebleRequest $request, Inmueble $inmueble)
    {
        $this->authorize('update', $inmueble);

        try {
            $validated = $request->validated();
            $validated['updated_by'] = auth()->id();

            $inmueble->update($validated);

            return redirect()->route('admin.inmuebles.index')
                ->with(['message' => 'Inmueble actualizado exitosamente.', 'alert-type' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error al actualizar inmueble: ' . $e->getMessage());
            return back()->withInput()
                ->with(['message' => 'Ocurrió un error al actualizar el inmueble.', 'alert-type' => 'error']);
        }
    }

    public function destroy(Inmueble $inmueble)
    {
        $this->authorize('delete', $inmueble);

        if ($inmueble->avaluos()->exists()) {
            return back()->with(['message' => 'No se puede eliminar: tiene avalúos asociados.', 'alert-type' => 'error']);
        }
        $inmueble->delete();
        return redirect()->route('admin.inmuebles.index')
            ->with(['message' => 'Inmueble eliminado.', 'alert-type' => 'success']);
    }

    public function ajaxSearch(Request $request)
    {
        $term = $request->get('q', '');

        $inmuebles = Inmueble::where(function($query) use ($term) {
                $query->where('catastro', 'LIKE', "%{$term}%")
                    ->orWhere('direccion', 'LIKE', "%{$term}%")
                    ->orWhere('matricula_rr', 'LIKE', "%{$term}%");
            })
            ->with('tipoInmueble')
            ->limit(20)
            ->get();

        $formatted = $inmuebles->map(function($inmueble) {
            return [
                'id' => $inmueble->id,
                'text' => $inmueble->catastro . ' - ' . $inmueble->direccion,
                'catastro' => $inmueble->catastro,
                'direccion' => $inmueble->direccion,
                'tipo_inmueble' => $inmueble->tipoInmueble->nombre ?? 'N/A',
                'valor_catastral' => $inmueble->valor_catastral
            ];
        });

        return response()->json(['results' => $formatted]);
    }
}
```

---

## 3. FORM REQUEST (STORE): app/Http/Requests/StoreInmuebleRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreInmuebleRequest extends FormRequest
{
    public function authorize() {
        return Gate::allows('create', \App\Models\Inmueble::class);
    }

    public function rules()
    {
        return [
            'complemento'                => 'nullable|string|max:3',
            'catastro'                   => 'required|string|max:15|unique:inmuebles|regex:/^[0-9]{2}-[0-9]{4}-[0-9]{2}-[0-9]{4}$/',
            'tipo_inmueble_id'           => 'required|exists:tipos_inmueble,id',
            'municipio_id'               => 'nullable|exists:municipios,id',
            'barrio_comunidad'           => 'nullable|string|max:100',
            'direccion'                  => 'nullable|string|max:200',
            'superficie_m2'              => 'nullable|numeric|min:0',
            'valor_catastral'            => 'required|numeric|min:0',
            'matricula_rr'               => 'nullable|string|max:20',
            'es_vivienda_unica_familiar' => 'boolean',
            'estado_inmueble'            => 'in:Activo,Transferido,Baja',
        ];
    }

    public function messages()
    {
        return [
            'catastro.regex' => 'El formato del número de catastro debe ser: XX-XXXX-XX-XXXX (ej: 10-1234-56-7890)',
        ];
    }
}
```

---

## 4. FORM REQUEST (UPDATE): app/Http/Requests/UpdateInmuebleRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateInmuebleRequest extends FormRequest
{
    public function authorize() {
        return Gate::allows('update', $this->route('inmueble'));
    }

    public function rules()
    {
        $id = $this->route('inmueble')->id;
        return [
            'complemento'                => 'nullable|string|max:3',
            'catastro'                   => "required|string|max:15|unique:inmuebles,catastro,$id|regex:/^[0-9]{2}-[0-9]{4}-[0-9]{2}-[0-9]{4}$/",
            'tipo_inmueble_id'           => 'required|exists:tipos_inmueble,id',
            'municipio_id'               => 'nullable|exists:municipios,id',
            'barrio_comunidad'           => 'nullable|string|max:100',
            'direccion'                  => 'nullable|string|max:200',
            'superficie_m2'              => 'nullable|numeric|min:0',
            'valor_catastral'            => 'required|numeric|min:0',
            'matricula_rr'               => 'nullable|string|max:20',
            'es_vivienda_unica_familiar' => 'boolean',
            'estado_inmueble'            => 'in:Activo,Transferido,Baja',
        ];
    }

    public function messages()
    {
        return [
            'catastro.regex' => 'El formato del número de catastro debe ser: XX-XXXX-XX-XXXX (ej: 10-1234-56-7890)',
        ];
    }
}
```

---

## 5. RUTAS: routes/web.php

```php
Route::middleware(['auth', 'can:browse_panel'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('inmuebles', InmuebleController::class)->names('admin.inmuebles');
    Route::get('inmuebles/ajax/list', [InmuebleController::class, 'list'])->name('admin.inmuebles.ajax.list');
    Route::get('inmuebles/ajax/search', [InmuebleController::class, 'ajaxSearch'])->name('admin.inmuebles.ajax.search');
});
```

---

## 6. VISTAS

### resources/views/admin/inmuebles/browse.blade.php

```php
@extends('voyager::master')

@section('page_title', 'Inmuebles')

@section('page_header')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered" style="margin-bottom: 0;">
                    <div class="panel-body" style="padding: 0;">
                        <div class="col-md-8" style="padding: 0;">
                            <h1 class="page-title">
                                <i class="fa-solid fa-building"></i> Inmuebles
                            </h1>
                        </div>
                        <div class="col-md-4 text-right" style="margin-top: 30px;">
                            @can('create', App\Models\Inmueble::class)
                                <a href="{{ route('admin.inmuebles.create') }}" class="btn btn-success">
                                    <i class="voyager-plus"></i> Nuevo Inmueble
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-sm-9">
                                <div class="dataTables_length">
                                    <label>Mostrar
                                        <select id="select-paginate" class="form-control input-sm">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select> registros
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <input type="text" id="input-search" class="form-control" placeholder="🔍 Buscar catastro o dirección...">
                            </div>
                        </div>
                        <div class="row" id="div-results" style="min-height: 120px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.modal-delete')
@stop

@push('javascript')
<script>
    window.countPage = 10;

    window.list = function (page = 1) {
        $('#div-results').loading({message: 'Cargando...'});
        const url   = '{{ route("admin.inmuebles.ajax.list") }}';
        const search = $('#input-search').val() || '';

        $.get(url, {search, paginate: window.countPage, page})
         .done(res => {
             $('#div-results').html(res);
         })
         .fail(xhr => console.error(xhr))
         .always(() => $('#div-results').loading('toggle'));
    };

    $(function () {
        window.list();
        $('#input-search').on('keyup', e => { if (e.which === 13) window.list(); })
                         .on('input',  () => { clearTimeout(window.t); window.t = setTimeout(window.list, 500); });
        $('#select-paginate').change(function () { window.countPage = $(this).val(); window.list(); });
    });

    function deleteItem(url) { $('#delete_form').attr('action', url); }
</script>
@endpush
```

### resources/views/admin/inmuebles/list.blade.php

```php
<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Catástro</th>
                <th>Tipo</th>
                <th>Municipio</th>
                <th class="text-center">Superficie (m²)</th>
                <th class="text-center">Valor Catastral</th>
                <th class="text-center">Viv. Única</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $inmueble)
                <tr>
                    <td><strong>{{ $inmueble->catastro }}</strong></td>
                    <td>{{ $inmueble->tipoInmueble->nombre }}</td>
                    <td>{{ $inmueble->municipio->nombre ?? '-' }}</td>
                    <td class="text-center">{{ number_format($inmueble->superficie_m2, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-primary">Bs. {{ number_format($inmueble->valor_catastral, 2) }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $inmueble->es_vivienda_unica_familiar ? 'success' : 'secondary' }}">
                            {{ $inmueble->es_vivienda_unica_familiar ? 'Sí' : 'No' }}
                        </span>
                    </td>
                    <td class="text-center">
                        @php
                            $badge = match($inmueble->estado_inmueble) {
                                'Activo'     => 'success',
                                'Transferido'=> 'warning',
                                'Baja'       => 'danger',
                                default      => 'secondary'
                            };
                        @endphp
                        <span class="badge badge-{{ $badge }}">{{ $inmueble->estado_inmueble }}</span>
                    </td>
                    <td class="text-right" style="width: 18%">
                        @can('view', $inmueble)
                            <a href="{{ route('admin.inmuebles.show', $inmueble) }}" class="btn btn-sm btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                        @can('update', $inmueble)
                            <a href="{{ route('admin.inmuebles.edit', $inmueble) }}" class="btn btn-sm btn-primary" title="Editar">
                                <i class="voyager-edit"></i>
                            </a>
                        @endcan
                        @can('delete', $inmueble)
                            <form action="{{ route('admin.inmuebles.destroy', $inmueble) }}" method="POST"
                                  style="display: inline-block;" onsubmit="return confirm('¿Borrar este inmueble?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Borrar">
                                    <i class="voyager-trash"></i>
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No hay inmuebles registrados
                        </h5>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="col-md-12">
    <div class="col-md-4 text-muted">
        @if($data->count())
            Mostrando del {{ $data->firstItem() }} al {{ $data->lastItem() }} de {{ $data->total() }} registros.
        @endif
    </div>
    <div class="col-md-8 text-right">
        <nav>{{ $data->appends(request()->only(['search', 'paginate']))->links() }}</nav>
    </div>
</div>

@if(request()->ajax())
<script>
    $(document).ready(function(){
        $('.page-link').click(function(e){
            e.preventDefault();
            let url = new URL($(this).attr('href'));
            let page = url.searchParams.get('page') || 1;

            if (typeof list === 'function') {
                list(page);
            } else {
                window.location.href = $(this).attr('href');
            }
        });
    });
</script>
@endif
```

### resources/views/admin/inmuebles/edit-add.blade.php

```php
@extends('voyager::master')

@section('page_title', ($inmueble->exists ?? false) ? 'Editar Inmueble' : 'Agregar Inmueble')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($inmueble->exists ?? false)
            ? route('admin.inmuebles.update', $inmueble)
            : route('admin.inmuebles.store') }}"
          method="POST">
        @csrf
        @if($inmueble->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa-solid fa-building"></i>
                    {{ ($inmueble->exists ?? false) ? 'Editar' : 'Agregar' }} Inmueble
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Complemento</label>
                        <input type="text" name="complemento" class="form-control"
                               value="{{ old('complemento', optional($inmueble)->complemento) }}"
                               maxlength="3" placeholder="Ej: A">
                    </div>

                    <div class="col-md-3">
                        <label>Catástro <span class="required">*</span></label>
                        <input type="text" name="catastro" class="form-control"
                               value="{{ old('catastro', optional($inmueble)->catastro) }}"
                               required maxlength="15" placeholder="Ej: 123456789012345">
                    </div>

                    <div class="col-md-3">
                        <label>Tipo Inmueble <span class="required">*</span></label>
                        <select name="tipo_inmueble_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($tipos as $t)
                                <option value="{{ $t->id }}"
                                    {{ old('tipo_inmueble_id', optional($inmueble)->tipo_inmueble_id) == $t->id ? 'selected' : '' }}>
                                    {{ $t->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Municipio</label>
                        <select name="municipio_id" class="form-control select2">
                            <option value="">Ninguno</option>
                            @foreach($municipios as $m)
                                <option value="{{ $m->id }}"
                                    {{ old('municipio_id', optional($inmueble)->municipio_id) == $m->id ? 'selected' : '' }}>
                                    {{ $m->nombre }} ({{ $m->provincia->departamento->codigo }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-4">
                        <label>Barrio / Comunidad</label>
                        <input type="text" name="barrio_comunidad" class="form-control"
                               value="{{ old('barrio_comunidad', optional($inmueble)->barrio_comunidad) }}"
                               maxlength="100" placeholder="Ej: Barrio Los Álamos">
                    </div>

                    <div class="col-md-4">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="form-control"
                               value="{{ old('direccion', optional($inmueble)->direccion) }}"
                               maxlength="200" placeholder="Ej: Av. 6 de Agosto #123">
                    </div>

                    <div class="col-md-4">
                        <label>Matrícula RR</label>
                        <input type="text" name="matricula_rr" class="form-control"
                               value="{{ old('matricula_rr', optional($inmueble)->matricula_rr) }}"
                               maxlength="20" placeholder="Ej: RR-123456">
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-3">
                        <label>Superficie (m²)</label>
                        <input type="number" step="0.01" min="0" name="superficie_m2" class="form-control"
                               value="{{ old('superficie_m2', optional($inmueble)->superficie_m2) }}"
                               placeholder="Ej: 250.50">
                    </div>

                    <div class="col-md-3">
                        <label>Valor Catastral <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="valor_catastral" class="form-control"
                               value="{{ old('valor_catastral', optional($inmueble)->valor_catastral) }}"
                               required placeholder="Ej: 500000.00">
                    </div>

                    <div class="col-md-3">
                        <label>Es Vivienda Única</label>
                        <select name="es_vivienda_unica_familiar" class="form-control">
                            <option value="0" {{ !old('es_vivienda_unica_familiar', optional($inmueble)->es_vivienda_unica_familiar) ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('es_vivienda_unica_familiar', optional($inmueble)->es_vivienda_unica_familiar) ? 'selected' : '' }}>Sí</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Estado</label>
                        <select name="estado_inmueble" class="form-control">
                            @php
                                $estados = ['Activo', 'Transferido', 'Baja'];
                                $current = old('estado_inmueble', optional($inmueble)->estado_inmueble ?? 'Activo');
                            @endphp
                            @foreach($estados as $e)
                                <option value="{{ $e }}" {{ $current == $e ? 'selected' : '' }}>{{ $e }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.inmuebles.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($inmueble->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop
```

### resources/views/admin/inmuebles/read.blade.php

```php
@extends('voyager::master')

@section('page_title', 'Ver Inmueble')

@section('content')
<div class="page-content container-fluid">
    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fa-solid fa-building"></i> Ver Inmueble: {{ $inmueble->catastro }}
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">ID</th>
                                <td>{{ $inmueble->id }}</td>
                            </tr>
                            <tr>
                                <th>Catástro</th>
                                <td><strong>{{ $inmueble->catastro }}</strong></td>
                            </tr>
                            <tr>
                                <th>Complemento</th>
                                <td>{{ $inmueble->complemento ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Tipo de Inmueble</th>
                                <td>{{ $inmueble->tipoInmueble->nombre }}</td>
                            </tr>
                            <tr>
                                <th>Municipio</th>
                                <td>
                                    @if($inmueble->municipio)
                                        {{ $inmueble->municipio->nombre }}
                                        ({{ $inmueble->municipio->provincia->departamento->nombre }})
                                    @else
                                        <span class="text-muted">Sin municipio</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Barrio / Comunidad</th>
                                <td>{{ $inmueble->barrio_comunidad ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Dirección</th>
                                <td>{{ $inmueble->direccion ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Superficie (m²)</th>
                                <td>{{ number_format($inmueble->superficie_m2, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Valor Catastral</th>
                                <td>
                                    <span class="badge badge-primary">Bs. {{ number_format($inmueble->valor_catastral, 2) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Matrícula RR</th>
                                <td>{{ $inmueble->matricula_rr ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Es Vivienda Única</th>
                                <td>
                                    <span class="badge badge-{{ $inmueble->es_vivienda_unica_familiar ? 'success' : 'secondary' }}">
                                        {{ $inmueble->es_vivienda_unica_familiar ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Estado del Inmueble</th>
                                <td>
                                    @php
                                        $badge = match($inmueble->estado_inmueble) {
                                            'Activo'      => 'success',
                                            'Transferido' => 'warning',
                                            'Baja'        => 'danger',
                                            default       => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $badge }}">{{ $inmueble->estado_inmueble }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Último Avalúo Vigente</th>
                                <td>
                                    @php
                                        $ultimo = $inmueble->avaluos()->where('estado', 'Vigente')->latest('fecha_avaluo')->first();
                                    @endphp
                                    @if($ultimo)
                                        <strong>{{ $ultimo->tipo_avaluo }}</strong> -
                                        Bs. {{ number_format($ultimo->valor, 2) }} -
                                        <small>{{ $ultimo->fecha_avaluo->format('d/m/Y') }}</small>
                                    @else
                                        <span class="text-muted">Sin avalúos vigentes</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Creado</th>
                                <td>{{ $inmueble->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Actualizado</th>
                                <td>{{ $inmueble->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            <a href="{{ route('admin.avaluos.index', ['inmueble_id' => $inmueble->id]) }}"
            class="btn btn-info">
                <i class="fa-solid fa-file-invoice-dollar"></i> Ver avalúos de este inmueble
            </a>
            <a href="{{ route('admin.inmuebles.index') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>

            @can('update', $inmueble)
                <a href="{{ route('admin.inmuebles.edit', $inmueble) }}" class="btn btn-primary">
                    <i class="voyager-edit"></i> Editar
                </a>
            @endcan
        </div>
    </div>
</div>
@stop
```

---

## 7. MIGRACIÓN

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmuebles', function (Blueprint $table) {
            $table->id();
            $table->string('complemento', 3)->nullable();
            $table->string('catastro', 15)->unique();
            $table->foreignId('tipo_inmueble_id')->constrained('tipos_inmueble');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios');
            $table->string('barrio_comunidad', 100)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->decimal('superficie_m2', 10, 2)->nullable();
            $table->decimal('valor_catastral', 12, 2);
            $table->string('matricula_rr', 20)->nullable();
            $table->boolean('es_vivienda_unica_familiar')->default(false);
            $table->enum('estado_inmueble', ['Activo', 'Transferido', 'Baja'])->default('Activo');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmuebles');
    }
};
```

---

## 8. POLICY: app/Policies/InmueblePolicy.php

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inmueble;

class InmueblePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', Inmueble::class);
    }

    public function view(User $user, Inmueble $inmueble): bool
    {
        return $user->can('view', $inmueble);
    }

    public function create(User $user): bool
    {
        return $user->can('create', Inmueble::class);
    }

    public function update(User $user, Inmueble $inmueble): bool
    {
        return $user->can('update', $inmueble);
    }

    public function delete(User $user, Inmueble $inmueble): bool
    {
        return $user->can('delete', $inmueble);
    }
}
```

---

## PATRONES A SEGUIR PARA NUEVOS CRUDs:

1. **Modelo**: Usar `SoftDeletes`, definir `$fillable` y `$casts`, agregar relaciones
2. **Controlador**:
   - Constructor con middleware auth
   - Todos los métodos CRUD (index, show, create, store, edit, update, destroy)
   - Usar `authorize()` para verificación de permisos
   - Manejo de errores con try-catch y Log
   - Verificar dependencias antes de eliminar
3. **Form Requests**: Separar validación de Store y Update
4. **Vistas**:
   - browse.blade.php: Página principal con AJAX
   - list.blade.php: Tabla con paginación dinámica
   - edit-add.blade.php: Formulario para create/edit
   - read.blade.php: Vista detallada
5. **Rutas**: Usar `Route::resource()` + rutas adicionales para AJAX
6. **Policy**: Definir permisos para cada acción
7. **Validación**: Usar Form Requests con rules() y messages()
