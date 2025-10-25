@extends('voyager::master')

@section('page_title', isset($tipoTransmision) ? 'Editar Tipo de Transmisión' : 'Añadir Tipo de Transmisión')

@section('page_header')
    <h1 class="page-title">
        <i class="fa-solid fa-arrow-right-arrow-left"></i>
        {{ isset($tipoTransmision) ? 'Editar Tipo de Transmisión' : 'Añadir Tipo de Transmisión' }}
    </h1>
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form"
                          action="{{ isset($tipoTransmision) ? route('admin.tipos-transmision.update', $tipoTransmision->id) : route('admin.tipos-transmision.store') }}"
                          method="POST">
                        @if(isset($tipoTransmision))
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
                                       placeholder="Ej: Herencia, Donación, Legado"
                                       value="{{ old('nombre', $tipoTransmision->nombre ?? '') }}">
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Guardar</button>
                            <a href="{{ route('admin.tipos-transmision.index') }}" class="btn btn-default">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
