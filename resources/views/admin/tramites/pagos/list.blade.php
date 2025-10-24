<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Fecha Pago</th>
                <th>Monto</th>
                <th class="text-center">Estado</th>
                <th>Banco</th>
                <th>Nro Operación</th>
                <th>Código Barras</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td>{{ $item->fecha_pago->format('d/m/Y H:i') }}</td>
                    <td><strong>Bs. {{ number_format($item->monto, 2) }}</strong></td>
                    <td class="text-center">
                        @php
                            $badge = match($item->estado) {
                                'Aplicado'   => 'success',
                                'Reversado'  => 'danger',
                                default      => 'warning'
                            };
                        @endphp
                        <span class="label label-{{ $badge }}">{{ $item->estado }}</span>
                    </td>
                    <td>{{ $item->banco ?? '—' }}</td>
                    <td><code>{{ $item->nro_operacion ?? '—' }}</code></td>
                    <td><small class="text-muted">{{ $item->codigo_barras }}</small></td>
                    <td class="text-center" style="width: 12%">
                        @can('view', $item)
                            <a href="{{ route('admin.tramites.pagos.show', [$tramite, $item]) }}" class="btn btn-xs btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                        @can('delete', $item)
                            @if($item->estado === 'Pendiente')
                                <form action="{{ route('admin.tramites.pagos.destroy', [$tramite, $item]) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Reversar pago?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" title="Reversar">
                                        <i class="voyager-trash"></i>
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No hay pagos registrados
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
