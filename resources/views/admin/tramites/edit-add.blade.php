@extends('voyager::master')

@section('page_title', ($tramite->exists ?? false) ? 'Editar Trámite IDTGB' : 'Agregar Trámite IDTGB')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <form action="{{ ($tramite->exists ?? false)
            ? route('admin.tramites.update', $tramite)
            : route('admin.tramites.store') }}"
          method="POST">
        @csrf
        @if($tramite->exists ?? false) @method('PUT') @endif

        <div class="panel panel-bordered panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa-solid fa-file-lines"></i>
                    {{ ($tramite->exists ?? false) ? 'Editar' : 'Agregar' }} Trámite IDTGB - A-01
                </h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    {{-- Número de trámite --}}
                    <div class="col-md-3">
                        <label>Nro Trámite <span class="required">*</span></label>
                        <input type="text" name="nro_tramite" class="form-control"
                               value="{{ old('nro_tramite', optional($tramite)->nro_tramite) }}"
                               required maxlength="15" placeholder="Ej: A-01-2025-0001">
                    </div>

                    {{-- Fecha de presentación --}}
                    <div class="col-md-3">
                        <label>Fecha Presentación <span class="required">*</span></label>
                        <input type="date" name="fecha_presentacion" class="form-control"
                               value="{{ old('fecha_presentacion', optional($tramite)->fecha_presentacion?->format('Y-m-d') ?? today()->format('Y-m-d')) }}"
                               required>
                    </div>

                    {{-- Tipo de transmisión --}}
                    <div class="col-md-3">
                        <label>Tipo Transmisión <span class="required">*</span></label>
                        <select name="tipo_transmision_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($tipos as $t)
                                <option value="{{ $t->id }}"
                                    {{ old('tipo_transmision_id', optional($tramite)->tipo_transmision_id) == $t->id ? 'selected' : '' }}>
                                    {{ $t->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Inmueble --}}
                    <div class="col-md-3">
                        <label>Inmueble <span class="required">*</span></label>
                       <select name="inmueble_id" class="form-control select2" required>
                            <option value="">Elija...</option>
                            @foreach($inmuebles as $i)
                                <option value="{{ $i->id }}">{{ $i->catastro }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    {{-- Valor declarado --}}
                    <div class="col-md-3">
                        <label>Valor Declarado (Bs) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="valor_declarado" class="form-control"
                               value="{{ old('valor_declarado', optional($tramite)->valor_declarado) }}"
                               required placeholder="Ej: 520000.00">
                    </div>

                    {{-- Base imponible --}}
                    <div class="col-md-3">
                        <label>Base Imponible (Bs) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="base_imponible" class="form-control"
                               value="{{ old('base_imponible', optional($tramite)->base_imponible) }}"
                               required placeholder="Ej: 500000.00">
                    </div>

                    {{-- Fecha de transmisión --}}
                    <div class="col-md-3">
                        <label>Fecha Transmisión <span class="required">*</span></label>
                        <input type="date" name="fecha_transmision" class="form-control"
                               value="{{ old('fecha_transmision', optional($tramite)->fecha_transmision?->format('Y-m-d')) }}"
                               required>
                    </div>

                    {{-- Estado --}}
                    <div class="col-md-3">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            @php
                                $estados = ['Borrador', 'Pagado', 'Observado', 'Anulado', 'Finalizado'];
                                $current = old('estado', optional($tramite)->estado ?? 'Borrador');
                            @endphp
                            @foreach($estados as $e)
                                <option value="{{ $e }}" {{ $current == $e ? 'selected' : '' }}>{{ $e }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row" style="margin-top: 15px;">
                    {{-- Observaciones --}}
                    <div class="col-md-12">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"
                                  placeholder="Ej: Escritura pública, testamento, etc.">{{ old('observaciones', optional($tramite)->observaciones) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="panel-footer text-right">
                <a href="{{ route('admin.tramites.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="voyager-check"></i> {{ ($tramite->exists ?? false) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </form>
</div>
@stop
