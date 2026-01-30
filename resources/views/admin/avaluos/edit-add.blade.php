@extends('voyager::master')

@section('page_title', ($avaluo->exists ?? false) ? 'Editar Avalúo' : 'Agregar Avalúo')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($avaluo->exists ?? false)
            ? route('admin.avaluos.update', $avaluo)
            : route('admin.avaluos.store') }}"
          method="POST"
          enctype="multipart/form-data">
        @csrf
        @if($avaluo->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    {{ ($avaluo->exists ?? false) ? 'Editar' : 'Agregar' }} Avalúo
                </h3>
            </div>

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
                <div class="row">
                    {{-- Inmueble --}}
                    <div class="col-md-4">
                        <label>Inmueble <span class="required">*</span></label>
                        <select name="inmueble_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($inmuebles as $i)
                                <option value="{{ $i->id }}"
                                    {{ old('inmueble_id', optional($avaluo)->inmueble_id) == $i->id ? 'selected' : '' }}>
                                    {{ $i->catastro }}
                                </option>
                            @endforeach
                        </select>
                        @error('inmueble_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Tipo de avalúo --}}
                    <div class="col-md-4">
                        <label>Tipo Avalúo <span class="required">*</span></label>
                        <select name="tipo_avaluo" class="form-control" required>
                            @php
                                $tipos = ['Fiscal', 'Comercial', 'Pericial'];
                                $current = old('tipo_avaluo', optional($avaluo)->tipo_avaluo ?? 'Fiscal');
                            @endphp
                            @foreach($tipos as $t)
                                <option value="{{ $t }}" {{ $current == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                        @error('tipo_avaluo')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Fecha del avalúo --}}
                    <div class="col-md-4">
                        <label>Fecha Avalúo <span class="required">*</span></label>
                        <input type="date" name="fecha_avaluo" class="form-control"
                               value="{{ old('fecha_avaluo', optional($avaluo)->fecha_avaluo?->format('Y-m-d')) }}"
                               required>
                        @error('fecha_avaluo')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    {{-- Valor (Bs) --}}
                    <div class="col-md-4">
                        <label>Valor (Bs) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="valor" class="form-control"
                               value="{{ old('valor', optional($avaluo)->valor) }}"
                               required placeholder="Ej: 520000.00">
                        @error('valor')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Perito --}}
                    <div class="col-md-4">
                        <label>Perito</label>
                        <select name="perito_id" class="form-control select2">
                            <option value="">Ninguno</option>
                            @foreach($peritos as $p)
                                <option value="{{ $p->id }}"
                                    {{ old('perito_id', optional($avaluo)->perito_id) == $p->id ? 'selected' : '' }}>
                                    {{ $p->first_name }} {{ $p->paternal_surname }}
                                </option>
                            @endforeach
                        </select>
                        @error('perito_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Estado --}}
                    <div class="col-md-4">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            @php
                                $estados = ['Vigente', 'Caducado'];
                                $current = old('estado', optional($avaluo)->estado ?? 'Vigente');
                            @endphp
                            @foreach($estados as $e)
                                <option value="{{ $e }}" {{ $current == $e ? 'selected' : '' }}>{{ $e }}</option>
                            @endforeach
                        </select>
                        @error('estado')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    {{-- Archivo adjunto --}}
                    <div class="col-md-12">
                        <label>Documento (pdf/jpg/png ≤ 5 MB)</label>
                        <input type="file" name="documento" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        @error('documento')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        @if($avaluo->exists && $avaluo->documento_path)
                            <div class="mt-2">
                                <small>Archivo actual:</small>
                                <a href="{{ route('admin.avaluos.download', $avaluo) }}" target="_blank" class="btn btn-xs btn-info">
                                    <i class="voyager-download"></i> Descargar
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.avaluos.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($avaluo->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop
