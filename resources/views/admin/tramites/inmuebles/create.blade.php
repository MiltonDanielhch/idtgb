@extends('voyager::master')

@section('page_title', 'Agregar Inmueble al Trámite '.$tramite->nro_tramite)

@section('page_header')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered" style="margin-bottom: 0;">
                    <div class="panel-body" style="padding: 0;">
                        <div class="col-md-8" style="padding: 0;">
                            <h1 class="page-title">
                                <i class="voyager-plus"></i> Agregar Inmueble al Trámite <strong>{{ $tramite->nro_tramite }}</strong>
                            </h1>
                        </div>
                        <div class="col-md-4 text-right" style="margin-top: 30px;">
                            <a href="{{ route('admin.tramites.inmuebles.index', $tramite) }}" class="btn btn-warning">
                                <i class="voyager-angle-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form action="{{ route('admin.tramites.inmuebles.store', $tramite) }}" method="POST">
                        @csrf

                        <div class="panel-body">
                            <div class="form-group col-md-6">
                                <label>Inmueble <span class="text-danger">*</span></label>
                                <select name="inmueble_id" class="form-control select2" required>
                                    <option value="">-- Seleccione --</option>
                                    @foreach($inmuebles as $inmueble)
                                        <option value="{{ $inmueble->id }}" {{ old('inmueble_id') == $inmueble->id ? 'selected' : '' }}>
                                            {{ $inmueble->catastro }} - {{ $inmueble->direccion }} (Bs. {{ number_format($inmueble->valor_catastral, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('inmueble_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <a href="{{ route('admin.tramites.inmuebles.index', $tramite) }}" class="btn btn-default">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: '-- Seleccione --',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@stop
