@extends('voyager::master')

@section('page_title', 'Agregar Valor UFV')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ route('admin.ufvs.store') }}" method="POST">
        @csrf

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-calendar"></i> Agregar Valor UFV - IDTGB Beni
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Fecha --}}
                    <div class="col-md-4">
                        <label>Fecha <span class="required">*</span></label>
                        <input type="date" name="fecha" class="form-control"
                               value="{{ old('fecha', today()->format('Y-m-d')) }}" required max="{{ today()->format('Y-m-d') }}">
                        @error('fecha') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Valor UFV --}}
                    <div class="col-md-4">
                        <label>Valor UFV <span class="required">*</span></label>
                        <input type="number" step="0.00001" min="0.00001" name="valor" class="form-control"
                               value="{{ old('valor') }}" required placeholder="Ej: 2.34567">
                        @error('valor') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Valor sugerido (solo lectura) --}}
                    <div class="col-md-4">
                        <label>Valor ayer</label>
                        <p class="form-control-static">
                            <span class="badge badge-info">
                                {{ optional(\App\Models\Ufv::whereDate('fecha', today()->subDay())->first())->valor ?? '—' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.ufvs.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> Guardar
                </button>
            </div>
        </div>
    </form>
</div>
@stop

{{-- @extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Nueva UFV</h3>
                </div>
                <form action="{{ route('admin.ufvs.store') }}" method="POST">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Fecha *</label>
                                <input type="date" name="fecha" class="form-control" required value="{{ old('fecha', today()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label>Valor *</label>
                                <input type="number" step="0.00001" min="0" max="999.99999" name="valor" class="form-control" required value="{{ old('valor') }}">
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('admin.ufvs.index') }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop --}}
