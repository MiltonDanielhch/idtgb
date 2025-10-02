@extends('voyager::master')

@section('page_title', 'Ver Inmueble')

@section('content')
<div class="page-content container-fluid">
    <div class="panel panel-bordered panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="fa-solid fa-building"></i> Ver Inmueble: {{ $inmueble->catastro }}
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">ID</th>
                                <td>{{ $inmueble->id }}</td>
                            </tr>
                            <tr>
                                <th>Catástro</th>
                                <td><strong>{{ $inmueble->catastro }}</strong></td>
                            </tr>
                            <tr>
                                <th>Complemento</th>
                                <td>{{ $inmueble->complemento ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Tipo de Inmueble</th>
                                <td>{{ $inmueble->tipoInmueble->nombre }}</td>
                            </tr>
                            <tr>
                                <th>Municipio</th>
                                <td>
                                    @if($inmueble->municipio)
                                        {{ $inmueble->municipio->nombre }}
                                        ({{ $inmueble->municipio->provincia->departamento->nombre }})
                                    @else
                                        <span class="text-muted">Sin municipio</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Barrio / Comunidad</th>
                                <td>{{ $inmueble->barrio_comunidad ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Dirección</th>
                                <td>{{ $inmueble->direccion ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Superficie (m²)</th>
                                <td>{{ number_format($inmueble->superficie_m2, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Valor Catastral</th>
                                <td>
                                    <span class="badge badge-primary">Bs. {{ number_format($inmueble->valor_catastral, 2) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Matrícula RR</th>
                                <td>{{ $inmueble->matricula_rr ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Es Vivienda Única</th>
                                <td>
                                    <span class="badge badge-{{ $inmueble->es_vivienda_unica_familiar ? 'success' : 'secondary' }}">
                                        {{ $inmueble->es_vivienda_unica_familiar ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Estado del Inmueble</th>
                                <td>
                                    @php
                                        $badge = match($inmueble->estado_inmueble) {
                                            'Activo'      => 'success',
                                            'Transferido' => 'warning',
                                            'Baja'        => 'danger',
                                            default       => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $badge }}">{{ $inmueble->estado_inmueble }}</span>
                                </td>
                            </tr>

                            {{-- ÚLTIMO AVALÚO VIGENTE --}}
                            <tr>
                                <th>Último Avalúo Vigente</th>
                                <td>
                                    @php
                                        $ultimo = $inmueble->avaluos()->where('estado', 'Vigente')->latest('fecha_avaluo')->first();
                                    @endphp
                                    @if($ultimo)
                                        <strong>{{ $ultimo->tipo_avaluo }}</strong> -
                                        Bs. {{ number_format($ultimo->valor, 2) }} -
                                        <small>{{ $ultimo->fecha_avaluo->format('d/m/Y') }}</small>
                                    @else
                                        <span class="text-muted">Sin avalúos vigentes</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <th>Creado</th>
                                <td>{{ $inmueble->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Actualizado</th>
                                <td>{{ $inmueble->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-footer text-right">
            {{-- Botón que filtra avalúos por este inmueble --}}
            <a href="{{ route('admin.avaluos.index', ['inmueble_id' => $inmueble->id]) }}"
            class="btn btn-info">
                <i class="fa-solid fa-file-invoice-dollar"></i> Ver avalúos de este inmueble
            </a>
            <a href="{{ route('admin.inmuebles.index') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Volver
            </a>

            @can('update', $inmueble)
                <a href="{{ route('admin.inmuebles.edit', $inmueble) }}" class="btn btn-primary">
                    <i class="voyager-edit"></i> Editar
                </a>
            @endcan
        </div>
    </div>
</div>
@stop
