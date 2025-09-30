@extends('voyager::master')

@section('content')
<div class="page-content edit container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Agregar Disponente al Trámite {{ $tramite->nro_tramite }}</h3>
                </div>
                <form action="{{ route('admin.tramites.disponentes.store', $tramite) }}" method="POST">
                    @csrf
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Persona *</label>
                                <select name="persona_id" class="form-control select2" required>
                                    <option value="">Elija...</option>
                                    @foreach($personas as $p)
                                        <option value="{{ $p->id }}">{{ $p->first_name.' '.$p->paternal_surname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Tipo *</label>
                                <select name="tipo" class="form-control" required>
                                    <option value="Causante">Causante</option>
                                    <option value="Donante">Donante</option>
                                    <option value="Testador">Testador</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Fecha de Fallecimiento</label>
                                <input type="date" name="fecha_fallecimiento" class="form-control" max="{{ today()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="row" style="margin-top:15px;">
                            <div class="col-md-12">
                                <label>
                                    <input type="checkbox" name="es_discapacitado" value="1">
                                    ¿Es persona con discapacidad?
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary">Agregar</button>
                        <a href="{{ route('admin.tramites.disponentes.index', $tramite) }}" class="btn btn-default">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
