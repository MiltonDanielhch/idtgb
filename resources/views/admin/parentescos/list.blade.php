<div class="col-md-12">
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Tasas</th>
                    <th>Trámites</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parentescos as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $p->tasas_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $p->adquirentes_tramite_count > 0 ? 'warning' : 'secondary' }}">
                            {{ $p->adquirentes_tramite_count }}
                        </span>
                    </td>
                    <td class="text-right" style="width: 30%">
                        @can('view', $p)
                            <a href="{{ route('admin.parentescos.show', $p) }}" title="Ver" class="btn btn-sm btn-warning">
                                <i class="voyager-eye"></i> Ver
                            </a>
                        @endcan
                        @can('update', $p)
                            <a href="{{ route('admin.parentescos.edit', $p) }}" title="Editar" class="btn btn-sm btn-primary">
                                <i class="voyager-edit"></i> Editar
                            </a>
                        @endcan
                        @can('delete', $p)
                            <button type="button"
                                    class="btn btn-sm btn-danger"
                                    title="Borrar"
                                    onclick="deleteItem('{{ route('admin.parentescos.destroy', $p) }}', '{{ $p->nombre }}')"
                                    data-toggle="modal"
                                    data-target="#delete_modal">
                                <i class="voyager-trash"></i> Borrar
                            </button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No se encontraron parentescos
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
        @if($parentescos->count())
            Mostrando del {{ $parentescos->firstItem() }} al {{ $parentescos->lastItem() }} de {{ $parentescos->total() }} registros.
        @endif
    </div>
    <div class="col-md-8 text-right">
        <nav class="text-right">
            {{ $parentescos->links() }}
        </nav>
    </div>
</div>

{{-- Script para paginación dinámica (solo si usas AJAX) --}}
@if(request()->ajax())
<script>
    $(document).ready(function(){
        $('.page-link').click(function(e){
            e.preventDefault();
            let url = new URL($(this).attr('href'));
            let page = url.searchParams.get('page') || 1;

            // Si tienes una función list() para AJAX
            if (typeof list === 'function') {
                list(page);
            } else {
                // Si no, redirige normalmente
                window.location.href = $(this).attr('href');
            }
        });
    });
</script>
@endif
