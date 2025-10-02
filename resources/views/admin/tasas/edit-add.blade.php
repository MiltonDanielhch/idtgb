@extends('voyager::master')

@section('page_title', ($tasa->exists ?? false) ? 'Editar Tasa' : 'Agregar Tasa')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($tasa->exists ?? false)
            ? route('admin.tasas.update', $tasa)
            : route('admin.tasas.store') }}"
          method="POST">
        @csrf
        @if($tasa->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-dollar"></i>
                    {{ ($tasa->exists ?? false) ? 'Editar' : 'Agregar' }} Tasa
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Departamento --}}
                    <div class="col-md-3">
                        <label>Departamento <span class="required">*</span></label>
                        <select name="departamento_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($departamentos as $d)
                                <option value="{{ $d->id }}"
                                    {{ old('departamento_id', optional($tasa)->departamento_id) == $d->id ? 'selected' : '' }}>
                                    {{ $d->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Parentesco --}}
                    <div class="col-md-3">
                        <label>Parentesco <span class="required">*</span></label>
                        <select name="parentesco_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($parentescos as $p)
                                <option value="{{ $p->id }}"
                                    {{ old('parentesco_id', optional($tasa)->parentesco_id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipo Transmisión --}}
                    <div class="col-md-3">
                        <label>Tipo Transmisión</label>
                        <select name="tipo_transmision_id" class="form-control select2">
                            <option value="">Ninguno</option>
                            @foreach($tipos as $t)
                                <option value="{{ $t->id }}"
                                    {{ old('tipo_transmision_id', optional($tasa)->tipo_transmision_id) == $t->id ? 'selected' : '' }}>
                                    {{ $t->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tasa (%) --}}
                    <div class="col-md-3">
                        <label>Tasa (%) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" max="99.99"
                               name="tasa" class="form-control"
                               value="{{ old('tasa', optional($tasa)->tasa) }}" required>
                    </div>

                    {{-- Vigente Desde --}}
                    <div class="col-md-6">
                        <label>Vigente Desde <span class="required">*</span></label>
                        <input type="date" name="vigente_desde" class="form-control"
                               value="{{ old('vigente_desde', optional($tasa)->vigente_desde?->format('Y-m-d')) }}" required>
                    </div>

                    {{-- Vigente Hasta --}}
                    <div class="col-md-6">
                        <label>Vigente Hasta</label>
                        <input type="date" name="vigente_hasta" class="form-control"
                               value="{{ old('vigente_hasta', optional($tasa)->vigente_hasta?->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.tasas.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($tasa->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop
