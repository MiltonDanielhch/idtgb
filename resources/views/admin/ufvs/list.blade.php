<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Valor UFV</th>
                <th class="text-center">Registrado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td><strong>{{ $item->fecha->format('d/m/Y') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($item->valor, 5) }}</strong></td>
                    <td class="text-center">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center" style="width: 10%">
                        @can('view', $item)
                            <a href="{{ route('admin.ufvs.show', $item) }}" class="btn btn-xs btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No hay valores UFV registrados
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
