@extends('voyager::master')

@section('page_title', 'Editar Valor UFV')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ route('admin.ufvs.update', $ufv->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="voyager-calendar"></i> Editar Valor UFV - IDTGB Beni
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Fecha --}}
                    <div class="col-md-6">
                        <label>Fecha <span class="required">*</span></label>
                        <input type="date" name="fecha" class="form-control"
                               value="{{ old('fecha', $ufv->fecha->format('Y-m-d')) }}" required max="{{ today()->format('Y-m-d') }}">
                        @error('fecha') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Valor UFV --}}
                    <div class="col-md-6">
                        <label>Valor UFV <span class="required">*</span></label>
                        <input type="number" step="0.00001" min="0.00001" name="valor" class="form-control"
                               value="{{ old('valor', $ufv->valor) }}" required placeholder="Ej: 2.34567">
                        @error('valor') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.ufvs.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> Actualizar
                </button>
            </div>
        </div>
    </form>
</div>
@stop
