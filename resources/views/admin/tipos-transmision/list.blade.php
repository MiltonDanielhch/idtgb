<div class="table-responsive">
    <table id="dataTable" class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th class="text-center">Tasas</th>
                <th class="text-center">Trámites</th>
                <th>Creado en</th>
                <th class="actions text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->nombre }}</td>
                    <td class="text-center">
                        <span class="badge badge-primary">{{ $item->tasas_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $item->tramites_count }}</span>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="no-sort no-click bread-actions">
                        @can('view', $item)
                            <a href="{{ route('admin.tipos-transmision.show', $item->id) }}" title="Ver" class="btn btn-sm btn-warning view">
                                <i class="voyager-eye"></i> <span class="hidden-xs hidden-sm">Ver</span>
                            </a>
                        @endcan
                        @can('update', $item)
                            <a href="{{ route('admin.tipos-transmision.edit', $item->id) }}" title="Editar" class="btn btn-sm btn-primary edit">
                                <i class="voyager-edit"></i> <span class="hidden-xs hidden-sm">Editar</span>
                            </a>
                        @endcan
                        @can('delete', $item)
                            @if($item->tasas_count == 0 && $item->tramites_count == 0)
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        title="Borrar"
                                        onclick="deleteItem('{{ route('admin.tipos-transmision.destroy', $item->id) }}', '{{ $item->nombre }}')"
                                        data-toggle="modal"
                                        data-target="#delete_modal">
                                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">Borrar</span>
                                </button>
                            @else
                                <button disabled class="btn btn-sm btn-default" title="No se puede eliminar - tiene dependencias">
                                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">Borrar</span>
                                </button>
                            @endif
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
        @if($data->total() > 0)
            <p class="text-muted">Mostrando del {{ $data->firstItem() }} al {{ $data->lastItem() }} de {{ $data->total() }} registros.</p>
        @endif
    </div>
    <div class="col-md-6">
        <nav class="pull-right">
            {{ $data->links() }}
        </nav>
    </div>
</div>
