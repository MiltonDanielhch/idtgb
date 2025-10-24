<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Versión</th>
                <th class="text-center">Vigente</th>
                <th>Persona</th>
                <th>Hash SHA-256</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td><strong>{{ $item->tipo_doc }}</strong></td>
                    <td><span class="badge badge-primary">v{{ $item->version }}</span></td>
                    <td class="text-center">
                        @if($item->vigente)
                            <span class="label label-success">Vigente</span>
                        @else
                            <span class="label label-default">Obsoleto</span>
                        @endif
                    </td>
                    <td>{{ optional($item->persona)->fullName ?? '—' }}</td>
                    <td><small class="text-muted">{{ Str::limit($item->hash_sha256, 16, '…') }}</small></td>
                    <td class="text-center" style="width: 15%">
                        <a href="{{ \Storage::disk('public')->url($item->file_path) }}" target="_blank" class="btn btn-xs btn-primary" title="Descargar">
                            <i class="voyager-download"></i>
                        </a>
                        @can('view', $item)
                            <a href="{{ route('admin.tramites.documentos.show', [$tramite, $item]) }}" class="btn btn-xs btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                        @can('delete', $item)
                            <form action="{{ route('admin.tramites.documentos.destroy', [$tramite, $item]) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Marcar como no vigente?')">
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
                            No hay documentos registrados
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
