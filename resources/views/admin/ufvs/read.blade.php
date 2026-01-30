@extends('voyager::master')

@section('page_title', 'Ver Valor UFV')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="voyager-eye"></i> Viendo Valor UFV
            </h3>
            <div class="panel-actions">
                <a href="{{ route('admin.ufvs.edit', $ufv->id) }}" class="btn btn-info">
                    <i class="voyager-edit"></i> Editar
                </a>
                <a href="{{ route('admin.ufvs.index') }}" class="btn btn-default">
                    <i class="voyager-angle-left"></i> Volver al listado
                </a>
            </div>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Fecha</h3>
                    </div>
                    <div class="panel-body" style="padding-top:0;">
                        <p>{{ $ufv->fecha->format('d/m/Y') }}</p>
                    </div>
                    <hr>
                </div>
                <div class="col-md-6">
                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Valor</h3>
                    </div>
                    <div class="panel-body" style="padding-top:0;">
                        <p>{{ number_format($ufv->valor, 5, ',', '.') }}</p>
                    </div>
                    <hr>
                </div>
                <div class="col-md-6">
                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Creado</h3>
                    </div>
                    <div class="panel-body" style="padding-top:0;">
                        <p>{{ $ufv->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <hr>
                </div>
                <div class="col-md-6">
                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Última Actualización</h3>
                    </div>
                    <div class="panel-body" style="padding-top:0;">
                        <p>{{ $ufv->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <hr>
                </div>
                 <div class="col-md-6">
                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Creado por</h3>
                    </div>
                    <div class="panel-body" style="padding-top:0;">
                        <p>{{ optional($ufv->creator)->name ?? 'N/A' }}</p>
                    </div>
                    <hr>
                </div>
                 <div class="col-md-6">
                    <div class="panel-heading" style="border-bottom:0;">
                        <h3 class="panel-title">Actualizado por</h3>
                    </div>
                    <div class="panel-body" style="padding-top:0;">
                        <p>{{ optional($ufv->editor)->name ?? 'N/A' }}</p>
                    </div>
                    <hr>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
