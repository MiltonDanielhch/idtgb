<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Exención</th>
                <th>Tipo</th>
                <th class="text-center">Valor</th>
                <th class="text-center">Monto Aplicado</th>
                <th class="text-center">Vigencia</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td><strong>{{ $item->exencion->nombre }}</strong></td>
                    <td>{{ $item->exencion->tipo }}</td>
                    <td class="text-center">
                        <span class="badge badge-info">
                            {{ $item->exencion->tipo === 'porcentaje' ? $item->exencion->valor.' %' : 'Bs. '.number_format($item->exencion->valor, 2) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">Bs. {{ number_format($item->monto_aplicado, 2) }}</span>
                    </td>
                    <td class="text-center">
                        @php
                            $badge = now()->between($item->exencion->vigente_desde, $item->exencion->vigente_hasta ?? now()->addCentury()) ? 'success' : 'danger';
                        @endphp
                        <span class="badge badge-{{ $badge }}">
                            {{ $item->exencion->vigente_desde->format('d/m/Y') }} - {{ $item->exencion->vigente_hasta?->format('d/m/Y') ?? '∞' }}
                        </span>
                    </td>
                    <td class="text-right" style="width: 12%">
                        @can('view', $item)
                            <a href="{{ route('admin.tramites.exenciones.show', [$tramite, $item]) }}"
                            class="btn btn-xs btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                        @can('delete', $item)
                            <form action="{{ route('admin.tramites.exenciones.destroy', [$tramite, $item]) }}" method="POST"
                                style="display: inline-block;" onsubmit="return confirm('¿Quitar exención?')">
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
                            No hay exenciones aplicadas
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
