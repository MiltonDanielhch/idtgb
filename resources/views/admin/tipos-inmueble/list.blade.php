<div class="table-responsive">
    <table id="dataTable" class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Inmuebles</th>
                <th>Creado por</th>
                <th>Creado en</th>
                <th class="actions text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->nombre }}</td>
                    <td>
                        @if($item->inmuebles_count > 0)
                            <span class="badge badge-info">{{ $item->inmuebles_count }}</span>
                        @else
                            <span class="badge badge-secondary">0</span>
                        @endif
                    </td>
                    <td>
                        @if($item->createdBy)
                            {{ $item->createdBy->name ?? $item->createdBy->email }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="no-sort no-click bread-actions">
                        @can('view', $item)
                            <a href="{{ route('admin.tipos-inmueble.show', $item->id) }}" title="Ver" class="btn btn-sm btn-warning view">
                                <i class="voyager-eye"></i> <span class="hidden-xs hidden-sm">Ver</span>
                            </a>
                        @endcan
                        @can('update', $item)
                            <a href="{{ route('admin.tipos-inmueble.edit', $item->id) }}" title="Editar" class="btn btn-sm btn-primary edit">
                                <i class="voyager-edit"></i> <span class="hidden-xs hidden-sm">Editar</span>
                            </a>
                        @endcan
                        @can('delete', $item)
                            <button type="button"
                                    class="btn btn-sm btn-danger delete"
                                    title="Borrar"
                                    data-delete-url="{{ route('admin.tipos-inmueble.destroy', $item->id) }}"
                                    data-delete-name="{{ e($item->nombre) }}"
                                    data-toggle="modal"
                                    data-target="#delete_modal">
                                <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">Borrar</span>
                            </button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No se encontraron registros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="col-md-12">
    <div class="col-md-6" style="overflow-x:auto">
        @if(count($data) > 0)
            <p class="text-muted">Mostrando del {{ $data->firstItem() }} al {{ $data->lastItem() }} de {{ $data->total() }} registros.</p>
        @endif
    </div>
    <div class="col-md-6">
        <nav class="pull-right">
            {{ $data->links() }}
        </nav>
    </div>
</div>

@if(request()->ajax())
<script>
    $(document).ready(function(){
        $('.page-link').click(function(e){
            e.preventDefault();
            let url = new URL($(this).attr('href'));
            let page = url.searchParams.get('page') || 1;
            fetch_data(page, '', $('#select-paginate').val());
        });
    });
</script>
@endif
