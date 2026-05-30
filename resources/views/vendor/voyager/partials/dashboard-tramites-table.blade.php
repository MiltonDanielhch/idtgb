{{-- resources/views/vendor/voyager/partials/dashboard-tramites-table.blade.php --}}
<div class="table-responsive">
    @if(config('app.debug'))
        <div class="alert alert-info">
            <strong>Debug:</strong>
            Tipo: {{ is_object($ultimosTramites) ? get_class($ultimosTramites) : gettype($ultimosTramites) }};
            Contador: {{ is_object($ultimosTramites) && method_exists($ultimosTramites, 'count') ? $ultimosTramites->count() : (is_array($ultimosTramites) ? count($ultimosTramites) : 0) }}
        </div>
    @endif
    <table class="table table-hover">
        <thead>
            <tr>
                <th># Trámite</th>
                <th>Contribuyente</th>
                <th>Fecha Presentación</th>
                <th>Monto Final (Bs.)</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="ultimos-tramites-body">
            @forelse ($ultimosTramites ?? [] as $tramite)
                <tr>
                    <td>{{ $tramite['nro_tramite'] }}</td>
                    <td>{{ $tramite['contribuyente'] }}</td>
                    <td>{{ $tramite['created_at'] }}</td>
                    <td>{{ number_format(round($tramite['monto_final'] * 2) / 2, 2, ',', '.') }}</td>
                    <td>
                        @php
                            $labelClass = match($tramite['estado']) {
                                'Pagado'     => 'label-success',
                                'Borrador'   => 'label-default',
                                'Observado'  => 'label-warning',
                                'Anulado'    => 'label-danger',
                                'Finalizado' => 'label-info',
                                default      => 'label-default'
                            };
                        @endphp
                        <span class="label {{ $labelClass }}">{{ $tramite['estado'] }}</span>
                    </td>
                    <td>
                        <a href="{{ route('admin.tramites.show', $tramite['id']) }}" class="btn btn-sm btn-primary">Ver</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay trámites recientes.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
