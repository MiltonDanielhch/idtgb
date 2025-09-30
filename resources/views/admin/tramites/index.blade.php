@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Trámites IDTGB - A-01</h3>
                    <a href="{{ route('admin.tramites.create') }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Nuevo Trámite
                    </a>
                </div>
                <div class="panel-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nro Trámite</th>
                                <th>Tipo Transmisión</th>
                                <th>Inmueble</th>
                                <th>Valor Declarado</th>
                                <th>Base Imponible</th>
                                <th>Total IDTGB</th>
                                <th>Estado</th>
                                <th>Vencimiento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tramites as $t)
                            <tr>
                                <td>{{ $t->nro_tramite }}</td>
                                <td>{{ $t->tipoTransmision->nombre }}</td>
                                <td>{{ $t->inmueble->catastro }}</td>
                                <td>{{ number_format($t->valor_declarado, 2) }}</td>
                                <td>{{ number_format($t->base_imponible, 2) }}</td>
                                <td><strong>{{ number_format($t->total_idtgb, 2) }}</strong></td>
                                <td>
                                    <span class="label label-{{
                                        $t->estado == 'Pagado' ? 'success' :
                                        ($t->estado == 'Borrador' ? 'default' :
                                        ($t->estado == 'Observado' ? 'warning' : 'danger')) }}">
                                        {{ $t->estado }}
                                    </span>
                                </td>
                                <td>{{ $t->fecha_vencimiento->format('d/m/Y') }}</td>
                                <td>
                                    {{-- siempre visibles --}}
                                    <a href="{{ route('admin.tramites.edit', $t) }}" class="btn btn-xs btn-primary" title="Editar">
                                        <i class="voyager-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.tramites.destroy', $t) }}" method="POST" style="display:inline;"
                                        onsubmit="return confirm('¿Borrar?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" title="Borrar">
                                            <i class="voyager-trash"></i>
                                        </button>
                                    </form>

                                    {{-- resto dentro del dropdown --}}
                                    <div class="btn-group dropup-xs" role="group">
                                        <button type="button" class="btn btn-default dropdown-toggle btn-xs" data-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false">Tramite
                                            <i class="voyager-wrench"></i> <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="{{ route('admin.tramites.a01', $t) }}" target="_blank">
                                                    <i class="voyager-documentation"></i> Form. A-01</a></li>

                                            @if($t->estado != 'Pagado')
                                                <li><a href="{{ route('admin.tramites.pagar', $t) }}">
                                                        <i class="voyager-dollar"></i> Registrar pago</a></li>
                                            @else
                                                <li><a href="{{ route('admin.pago.comprobante', $t->pago) }}">
                                                        <i class="voyager-check"></i> Comprobante</a></li>
                                            @endif

                                            <li role="separator" class="divider"></li>

                                            {{-- dropdown de la fila --}}
                                            <li><a href="{{ route('admin.tramites.exenciones.index', $t) }}">
                                                    <i class="voyager-gift"></i> Exenciones</a></li>

                                            <li><a href="{{ route('admin.tramites.adquirentes.index', $t) }}">
                                                    <i class="voyager-people"></i> Adquirentes</a></li>

                                            <li><a href="{{ route('admin.tramites.disponentes.index', $t) }}">
                                                    <i class="voyager-person"></i> Disponentes</a></li>

                                            <li><a href="{{ route('admin.tramites.documentos.index', $t) }}">
                                                    <i class="voyager-folder"></i> Documentos</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $tramites->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop
