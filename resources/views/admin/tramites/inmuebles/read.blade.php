@extends('admin.layout')

@section('content')
    <div class="page-header">
        <h1>Detalle del Inmueble en el Trámite</h1>
        <div class="page-header-actions">
            <a href="{{ route('admin.tramites.inmuebles.index', $tramite) }}" class="btn btn-primary">
                <i class="voyager-list"></i> Volver a la lista
            </a>
        </div>
    </div>

    <div class="panel panel-bordered">
        <div class="panel-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 30%;">Trámite</th>
                        <td>{{ $tramite->nro_tramite ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Inmueble ID</th>
                        <td>{{ $item->inmueble_id }}</td>
                    </tr>
                    <tr>
                        <th>Número de Catastro</th>
                        <td>{{ $item->inmueble->catastro ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Tipo de Inmueble</th>
                        <td>{{ $item->inmueble->tipoInmueble->nombre ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Municipio</th>
                        <td>{{ $item->inmueble->municipio->nombre ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Dirección</th>
                        <td>{{ $item->inmueble->direccion ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Superficie</th>
                        <td>{{ $item->inmueble->superficie_m2 ?? 'N/A' }} m²</td>
                    </tr>
                    <tr>
                        <th>Valor Catastral</th>
                        <td>{{ number_format($item->inmueble->valor_catastral, 2) ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Estado</th>
                        <td>{{ $item->inmueble->estado_inmueble ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Fecha de Asociación</th>
                        <td>{{ $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
