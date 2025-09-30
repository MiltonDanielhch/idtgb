@extends('voyager::master')

@section('content')
<div class="page-content browse container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <h3 class="panel-title">Adquirentes del Trámite {{ $tramite->nro_tramite }}</h3>
                    <a href="{{ route('admin.tramites.adquirentes.create', $tramite) }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> Agregar Adquirente
                    </a>
                </div>
                <div class="panel-body">
                    @if($adquirentes->isEmpty())
                        <p class="text-center">No hay adquirentes registrados.</p>
                    @else
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Persona</th>
                                    <th>Parentesco</th>
                                    <th>%</th>
                                    <th>Tasa Aplicada</th>
                                    <th>IDTGB Prop.</th>
                                    <th>Exención</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($adquirentes as $a)
                                <tr>
                                    <td>{{ $a->persona->first_name.' '.$a->persona->paternal_surname }}</td>
                                    <td>{{ $a->parentesco->nombre }}</td>
                                    <td>{{ $a->porcentaje }} %</td>
                                    <td>{{ $a->tasa_aplicada }} %</td>
                                    <td><strong>Bs {{ number_format($a->idtgb_proporcional, 2) }}</strong></td>
                                    <td>
                                        @if($a->es_beneficiario_exencion)
                                            <span class="label label-success">Sí</span>
                                            @if($a->documento_sustento_exencion)
                                                <a href="{{ \Storage::url($a->documento_sustento_exencion) }}" target="_blank" class="btn btn-xs btn-info">
                                                    <i class="voyager-eye"></i>
                                                </a>
                                            @endif
                                        @else
                                            <span class="label label-default">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.tramites.adquirentes.destroy', [$tramite, $a]) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('¿Quitar?')">
                                                <i class="voyager-trash"></i> Quitar
                                            </button>
                                        </form>
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
