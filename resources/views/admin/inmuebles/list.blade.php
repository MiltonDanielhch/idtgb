<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Catástro</th>
                <th>Tipo</th>
                <th>Municipio</th>
                <th class="text-center">Superficie (m²)</th>
                <th class="text-center">Valor Catastral</th>
                <th class="text-center">Viv. Única</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $inmueble)
                <tr>
                    <td><strong>{{ $inmueble->catastro }}</strong></td>
                    <td>{{ $inmueble->tipoInmueble->nombre }}</td>
                    <td>{{ $inmueble->municipio->nombre ?? '-' }}</td>
                    <td class="text-center">{{ number_format($inmueble->superficie_m2, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-primary">Bs. {{ number_format($inmueble->valor_catastral, 2) }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $inmueble->es_vivienda_unica_familiar ? 'success' : 'secondary' }}">
                            {{ $inmueble->es_vivienda_unica_familiar ? 'Sí' : 'No' }}
                        </span>
                    </td>
                    <td class="text-center">
                        @php
                            $badge = match($inmueble->estado_inmueble) {
                                'Activo'     => 'success',
                                'Transferido'=> 'warning',
                                'Baja'       => 'danger',
                                default      => 'secondary'
                            };
                        @endphp
                        <span class="badge badge-{{ $badge }}">{{ $inmueble->estado_inmueble }}</span>
                    </td>
                    <td class="text-right" style="width: 18%">
                       {{-- Botón que filtra avalúos por este inmueble --}}
                        <a href="{{ route('admin.avaluos.index', ['inmueble_id' => $inmueble->id]) }}"
                        class="btn btn-xs btn-info" title="Ver avalúos de este inmueble">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Avalúos
                        </a>

                        @can('view', $inmueble)
                            <a href="{{ route('admin.inmuebles.show', $inmueble) }}" class="btn btn-sm btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                        @can('update', $inmueble)
                            <a href="{{ route('admin.inmuebles.edit', $inmueble) }}" class="btn btn-sm btn-primary" title="Editar">
                                <i class="voyager-edit"></i>
                            </a>
                        @endcan
                        @can('delete', $inmueble)
                            <form action="{{ route('admin.inmuebles.destroy', $inmueble) }}" method="POST"
                                  style="display: inline-block;" onsubmit="return confirm('¿Borrar este inmueble?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Borrar">
                                    <i class="voyager-trash"></i>
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No hay inmuebles registrados
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
