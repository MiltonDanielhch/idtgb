<div class="col-md-12">
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parentescos as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td class="text-right">
                        {{-- Botones visibles directamente --}}
                         <a href="{{ route('admin.parentescos.show', $p) }}" title="Ver" class="btn btn-sm btn-warning">
                            <i class="voyager-eye"></i> Ver
                        </a>
                        <a href="{{ route('admin.parentescos.edit', $p) }}" title="Editar" class="btn btn-sm btn-primary edit">
                            <i class="voyager-edit"></i> Editar
                        </a>
                        <form action="{{ route('admin.parentescos.destroy', $p) }}" method="POST"
                              style="display: inline-block;"
                              onsubmit="return confirm('¿Borrar este registro?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger delete" title="Borrar">
                                <i class="voyager-trash"></i> Borrar
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3">
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


{{-- Paginación con estilo como el primer ejemplo --}}
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
<script>
    var page = "{{ request('page') }}";
    $(document).ready(function(){
        $('.page-link').click(function(e){
            e.preventDefault();
            let url = new URL($(this).attr('href'));
            let page = url.searchParams.get('page') || 1;
            list(page); // Asegúrate de que esta función exista
        });
    });
</script>
