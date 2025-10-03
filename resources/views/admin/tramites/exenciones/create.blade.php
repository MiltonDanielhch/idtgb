@extends('voyager::master')

@section('page_title', 'Aplicar Exención al Trámite '.$tramite->nro_tramite)

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ route('admin.tramites.exenciones.store', $tramite) }}" method="POST">
        @csrf

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-gift"></i> Aplicar Exención - Trámite {{ $tramite->nro_tramite }}
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Exención --}}
                    <div class="col-md-6">
                        <label>Exención <span class="required">*</span></label>
                        <select name="exencion_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($exenciones as $e)
                                <option value="{{ $e->id }}"
                                    {{ old('exencion_id') == $e->id ? 'selected' : '' }}>
                                    {{ $e->nombre }} -
                                    {{ $e->tipo === 'porcentaje' ? $e->valor.' %' : 'Bs. '.number_format($e->valor, 2) }}
                                    (vigente hasta {{ $e->vigente_hasta?->format('d/m/Y') ?? '∞' }})
                                </option>
                            @endforeach
                        </select>
                        @error('exencion_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Monto aplicado --}}
                    <div class="col-md-6">
                        <label>Monto Aplicado (Bs) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="monto_aplicado" class="form-control"
                               value="{{ old('monto_aplicado') }}" required
                               placeholder="Ej: 5000.00">
                        @error('monto_aplicado') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.tramites.exenciones.index', $tramite) }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> Aplicar
                </button>
            </div>
        </div>
    </form>
</div>
@stop

@section('javascript')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Elija...',
            allowClear: true,
            width: '100%'
        });
    });
</script>
@stop
