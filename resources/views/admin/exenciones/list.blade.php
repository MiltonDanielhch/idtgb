<div class="col-md-12">
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Monto Máx.</th>
                    <th>Vigente Desde</th>
                    <th>Vigente Hasta</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exenciones as $e)
                <tr>
                    <td>{{ $e->id }}</td>
                    <td>{{ $e->nombre }}</td>
                    <td>{{ ucfirst($e->tipo) }}</td>
                    <td>{{ number_format($e->valor, 2) }}</td>
                    <td>{{ $e->monto_maximo ? 'Bs '.number_format($e->monto_maximo, 2) : '-' }}</td>
                    <td>{{ $e->vigente_desde->format('d/m/Y') }}</td>
                    <td>{{ $e->vigente_hasta?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-right">
                        @can('view', $e)
                            <a href="{{ route('admin.exenciones.show', $e) }}" title="Ver" class="btn btn-sm btn-warning">
                                <i class="voyager-eye"></i> Ver
                            </a>
                        @endcan
                        @can('update', $e)
                            <a href="{{ route('admin.exenciones.edit', $e) }}" title="Editar" class="btn btn-sm btn-primary">
                                <i class="voyager-edit"></i> Editar
                            </a>
                        @endcan
                        @can('delete', $e)
                            <button type="button"
                                    class="btn btn-sm btn-danger"
                                    title="Borrar"
                                    onclick="deleteItem('{{ route('admin.exenciones.destroy', $e) }}', '{{ $e->nombre }}')"
                                    data-toggle="modal"
                                    data-target="#delete_modal">
                                <i class="voyager-trash"></i> Borrar
                            </button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No se encontraron exenciones
                        </h5>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="col-md-12">
    <div class="col-md-4 text-muted">
        @if($exenciones->count())
            Mostrando del {{ $exenciones->firstItem() }} al {{ $exenciones->lastItem() }} de {{ $exenciones->total() }} registros.
        @endif
    </div>
    <div class="col-md-8 text-right">
        <nav>{{ $exenciones->links() }}</nav>
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

{{-- @if(request()->ajax())
<script>
    $('.page-link').click(function(e){
        e.preventDefault();
        let url = new URL($(this).attr('href'));
        let page = url.searchParams.get('page') || 1;
        if (typeof list === 'function') list(page);
    });
</script> --}}
@endif

