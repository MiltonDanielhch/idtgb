<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Nro Trámite</th>
                <th>Nombre Adquirente</th>
                <th>CI</th>
                <th>Tipo Transmisión</th>
                <th class="text-center">Base Imponible</th>
                <th class="text-center">Monto Final</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Vencimiento</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $t)
                @php $adquirente = $t->adquirentes->first(); @endphp
                <tr>
                    <td><strong>{{ $t->nro_tramite }}</strong></td>
                    <td>{{ optional($adquirente?->person)->fullName ?? '-' }}</td>
                    <td>{{ optional($adquirente?->person)->ci ?? '-' }}</td>
                    <td>{{ $t->tipoTransmision->nombre }}</td>
                    <td class="text-center">
                        <span class="badge badge-primary">Bs. {{ number_format($t->base_imponible, 2) }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">Bs. {{ number_format(round($t->monto_final * 2) / 2, 2) }}</span>
                    </td>
                    <td class="text-center">
                        @php
                            $badge = match($t->estado) {
                                'Pagado'     => 'success',
                                'Borrador'   => 'default',
                                'Observado'  => 'warning',
                                'Anulado'    => 'danger',
                                'Finalizado' => 'info',
                                default      => 'secondary'
                            };
                        @endphp
                        <span class="badge badge-{{ $badge }}">{{ $t->estado }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-{{ now()->gt($t->fecha_vencimiento) ? 'danger' : 'muted' }}">
                            {{ $t->fecha_vencimiento->format('d/m/Y') }}
                        </span>
                    </td>
                    <td class="text-right" style="width: 22%">
                        {{-- Botones siempre visibles --}}
                        @can('view', $t)
                            <a href="{{ route('admin.tramites.show', $t) }}" class="btn btn-xs btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                        @can('update', $t)
                            @if(in_array($t->estado, ['Finalizado', 'Anulado', 'Pagado']))
                                <button class="btn btn-xs btn-primary" disabled title="No se puede editar">
                                    <i class="voyager-edit"></i>
                                </button>
                            @else
                                <a href="{{ route('admin.tramites.simple.edit', $t) }}" class="btn btn-xs btn-primary" title="Editar">
                                    <i class="voyager-edit"></i>
                                </a>
                            @endif
                        @endcan
                        @can('delete', $t)
                            <form action="{{ route('admin.tramites.destroy', $t) }}" method="POST"
                                  style="display: inline-block;" onsubmit="return confirm('¿Borrar este trámite?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" title="Borrar">
                                    <i class="voyager-trash"></i>
                                </button>
                            </form>
                        @endcan

                        {{-- Dropdown de acciones --}}
                        <div class="btn-group dropup-xs" role="group">
                            <button type="button" class="btn btn-default dropdown-toggle btn-xs" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                <i class="voyager-wrench"></i> Más <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="{{ route('admin.tramites.a01', $t) }}" target="_blank">
                                        <i class="voyager-documentation"></i> Form. A-01</a></li>

                                @if($t->estado !== 'Pagado')
                                    <li><a href="{{ route('admin.tramites.pagos.create', $t) }}">
                                        <i class="voyager-dollar"></i> Registrar pago</a></li>
                                @else
                                    @if($t->pago)
                                        <li><a href="{{ route('admin.tramites.pagos.show', [$t, $t->pago]) }}" target="_blank">
                                                <i class="voyager-check"></i> Comprobante</a></li>
                                    @endif
                                @endif
                                <li role="separator" class="divider"></li>

                                <li><a href="{{ route('admin.tramites.adquirentes.index', $t) }}">
                                        <i class="voyager-people"></i> Adquirentes</a></li>

                                <li><a href="{{ route('admin.tramites.documentos.index', $t) }}">
                                        <i class="voyager-folder"></i> Documentos</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No hay trámites registrados
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

{{-- Script para paginación dinámica --}}
@if(request()->ajax())
<script>
    $(document).ready(function(){
        $('.page-link').click(function(e){
            e.preventDefault();
            let url = new URL($(this).attr('href'));
            let page = url.searchParams.get('page') || 1;

            if (typeof list === 'function') {
                list(page);
            } else {
                window.location.href = $(this).attr('href');
            }
        });
    });
</script>
@endif
{{-- <script>bindPageLinks();</script> --}}
