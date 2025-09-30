@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Exenciones aplicadas al trámite {{ $tramite->nro_tramite }}</h3>
                    <a href="{{ route('admin.tramites.exenciones.create', $tramite) }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Aplicar nueva exención
                    </a>
                </div>
                <div class="panel-body">
                    @if($aplicadas->isEmpty())
                        <p class="text-center">No hay exenciones aplicadas.</p>
                    @else
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Exención</th>
                                    <th>Tipo</th>
                                    <th>Valor Original</th>
                                    <th>Monto Aplicado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($aplicadas as $e)
                                @php
                                    // Buscamos el registro pivot real
                                    $pivotModel = \App\Models\TramiteExencion::where('tramite_id', $tramite->id)
                                                                            ->where('exencion_id', $e->id)
                                                                            ->first();
                                @endphp
                                <tr>
                                    <td>{{ $e->nombre }}</td>
                                    <td>{{ ucfirst($e->tipo) }}</td>
                                    <td>{{ number_format($e->valor, 2) }}</td>
                                    <td><strong>Bs {{ number_format($e->pivot->monto_aplicado, 2) }}</strong></td>
                                    <td>
                                        @if($pivotModel)
                                        <form action="{{ route('admin.tramites.exenciones.destroy', [$tramite, $pivotModel]) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-danger" onclick="return confirm('¿Quitar?')">
                                                <i class="voyager-trash"></i> Quitar
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop
