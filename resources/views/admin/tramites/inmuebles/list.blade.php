<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Código Catastral</th>
                <th>Tipo Inmueble</th>
                <th class="text-center">Superficie (m²)</th>
                <th class="text-center">Valor Catastral</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td><strong>{{ $item->inmueble->catastro }}</strong></td>
                    <td>{{ $item->inmueble->tipoInmueble->nombre }}</td>
                    <td class="text-center">{{ number_format($item->inmueble->superficie_m2, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-primary">Bs. {{ number_format($item->inmueble->valor_catastral, 2) }}</span>
                    </td>
                    <td class="text-center">
                        @php
                            $badge = match($item->inmueble->estado_inmueble) {
                                'Activo'      => 'success',
                                'Transferido' => 'warning',
                                'Baja'        => 'danger',
                                default       => 'secondary'
                            };
                        @endphp
                        <span class="badge badge-{{ $badge }}">{{ $item->inmueble->estado_inmueble }}</span>
                    </td>
                    <td class="text-right" style="width: 12%">
                        @can('delete', $item)
                            <form action="{{ route('admin.tramites.inmuebles.destroy', [$tramite, $item]) }}" method="POST"
                                  style="display: inline-block;" onsubmit="return confirm('¿Quitar este inmueble del trámite?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" title="Quitar">
                                    <i class="voyager-trash"></i>
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No hay inmuebles agregados a este trámite
                        </h5>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="col-md-12">
    <div class="col-md-4 text-muted">
        @if($data->count())
            Mostrando del {{ $data->firstItem() }} al {{ $data->lastItem() }} de {{ $data->total() }} registros.
        @endif
    </div>
    <div class="col-md-8 text-right">
        <nav>{{ $data->appends(request()->only(['search', 'paginate']))->links() }}</nav>
    </div>
</div>

<script>bindPageLinks();</script>
