@extends('voyager::master')

@section('page_title', isset($tipoInmueble) ? 'Editar Tipo de Inmueble' : 'Añadir Tipo de Inmueble')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-home"></i>
        {{ isset($tipoInmueble) ? 'Editar Tipo de Inmueble' : 'Añadir Tipo de Inmueble' }}
    </h1>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form"
                          action="{{ isset($tipoInmueble) ? route('admin.tipos-inmueble.update', $tipoInmueble->id) : route('admin.tipos-inmueble.store') }}"
                          method="POST">
                        @if(isset($tipoInmueble))
                            @method('PUT')
                        @endif
                        @csrf

                        <div class="panel-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" class="form-control" name="nombre" id="nombre"
                                       placeholder="Ej: Urbano, Rústico"
                                       value="{{ old('nombre', $tipoInmueble->nombre ?? '') }}">
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Guardar</button>
                            <a href="{{ route('admin.tipos-inmueble.index') }}" class="btn btn-default">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
