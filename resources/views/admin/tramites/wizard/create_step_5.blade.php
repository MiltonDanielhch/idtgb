@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 5')

@section('wizard-content')
<div class="panel panel-bordered">
    <div class="panel-body">
        <h4 class="text-muted">{{ $step_title }}</h4>
        <hr>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Resumen de Datos Generales -->
        <div class="row">
            <div class="col-md-12">
                <h5>Datos Generales</h5>
                <p><strong>Nro. Trámite:</strong> {{ $tramite->nro_tramite }}</p>
                <p><strong>Fecha Presentación:</strong> {{ $tramite->fecha_presentacion->format('d/m/Y') }}</p>
                <p><strong>Fecha Transmisión:</strong> {{ $tramite->fecha_transmision->format('d/m/Y') }}</p>
                <p><strong>Tipo Transmisión:</strong> {{ optional(\App\Models\TipoTransmision::find($tramite->tipo_transmision_id))->nombre }}</p>
                <p><strong>Valor Declarado:</strong> Bs. {{ number_format($tramite->valor_declarado, 2) }}</p>
                <p><strong>Base Imponible:</strong> Bs. {{ number_format($tramite->base_imponible, 2) }}</p>
                <p><strong>Observaciones:</strong> {{ $tramite->observaciones ?: 'N/A' }}</p>
                <hr>
            </div>
        </div>

        <!-- Resumen de Disponentes -->
        <div class="row">
            <div class="col-md-12">
                <h5>Disponentes</h5>
                <ul>
                    @forelse ($tramite->disponentes_collection as $disponente)
                        <li>{{ $disponente->full_name }} (CI: {{ $disponente->ci }})</li>
                    @empty
                        <li class="text-muted">No hay disponentes.</li>
                    @endforelse
                </ul>
                <hr>
            </div>
        </div>

        <!-- Resumen de Adquirentes -->
        <div class="row">
            <div class="col-md-12">
                <h5>Adquirentes</h5>
                <ul>
                    @forelse ($tramite->adquirentes_collection as $adquirente)
                        <li>{{ $adquirente->full_name }} (CI: {{ $adquirente->ci }})</li>
                    @empty
                        <li class="text-muted">No hay adquirentes.</li>
                    @endforelse
                </ul>
                <hr>
            </div>
        </div>

        <!-- Resumen del Inmueble -->
        <div class="row">
            <div class="col-md-12">
                <h5>Inmueble</h5>
                @if ($tramite->inmueble)
                    <p><strong>Matrícula:</strong> {{ $tramite->inmueble->numero_matricula }}</p>
                    <p><strong>Dirección:</strong> {{ $tramite->inmueble->direccion }}</p>
                @else
                    <p class="text-muted">No se ha seleccionado un inmueble.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="panel-footer">
        <a href="{{ route('admin.tramites.wizard.create.step4') }}" class="btn btn-default">Anterior</a>
        <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">Cancelar</a>
        <form action="{{ route('admin.tramites.wizard.store') }}" method="POST" style="display: inline-block; float: right;">
            @csrf
            <button type="submit" class="btn btn-success">Confirmar y Guardar Trámite</button>
        </form>
    </div>
</div>
@endsection
