@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 6')

@section('wizard-content')
<div class="panel panel-bordered">
    <div class="panel-body">
        <h4 class="text-muted">{{ $step_title }}</h4>
        <hr>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Formulario para añadir exenciones --}}
        <form action="{{ route('admin.tramites.wizard.add.exencion') }}" method="POST" class="form-inline">
            @csrf
            <div class="form-group">
                <label for="exencion_id">Seleccionar Exención:</label>
                <select name="exencion_id" id="exencion_id" class="form-control" required>
                    <option value="">-- Elija una exención --</option>
                    @foreach($exencionesDisponibles as $exencion)
                        <option value="{{ $exencion->id }}">{{ $exencion->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="voyager-plus"></i> Añadir Exención
            </button>
        </form>
        <small class="text-muted" style="display: block; margin-top: 10px;">
            Seleccione las exenciones legales que aplican a este trámite.
        </small>
    </div>
</div>

{{-- Formulario principal del paso --}}
<form action="{{ route('admin.tramites.wizard.post.step6') }}" method="POST">
    @csrf
    <div class="panel panel-bordered" style="margin-top: 20px;">
        <div class="panel-body">
            <h5>Exenciones Aplicadas a este Trámite</h5>

            @if($exencionesSeleccionadas->count() > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre de la Exención</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exencionesSeleccionadas as $exencion)
                            <tr>
                                <td>{{ $exencion->nombre }}</td>
                                <td>{{ $exencion->descripcion }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $exencion->tipo)) }}</td>
                                <td>
                                    @if($exencion->tipo == 'porcentaje')
                                        {{ number_format($exencion->valor, 2) }}%
                                    @else
                                        {{ number_format($exencion->valor, 2) }} Bs.
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.tramites.wizard.remove.exencion', $exencion->id) }}"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Quitar la exención \'{{ $exencion->nombre }}\'?')">
                                        <i class="voyager-trash"></i> Quitar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-info text-center">
                    <i class="voyager-gift"></i> No se han aplicado exenciones a este trámite.
                </div>
            @endif
        </div>

        <div class="panel-footer">
            <a href="{{ route('admin.tramites.wizard.create.step5') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Anterior
            </a>
            <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">
                <i class="voyager-x"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary pull-right">
                Siguiente <i class="voyager-angle-right"></i>
            </button>
        </div>
    </div>
</form>
@endsection

@push('javascript')
<script>
    // Puedes añadir JS aquí si es necesario, por ejemplo, para Select2
</script>
@endpush
