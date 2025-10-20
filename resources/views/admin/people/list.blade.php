<div class="table-responsive">
    <table class="table table-hover table-voyager">
        <thead>
            <tr>
                <th style="text-align: center">#</th>
                <th style="text-align: center">Foto</th>
                <th style="text-align: center">Nombre completo / Razón social</th>
                <th style="text-align: center">Documento</th>
                <th style="text-align: center">Edad</th>
                <th style="text-align: center">Teléfono</th>
                <th style="text-align: center">Ubicación</th> <!-- ✅ NUEVA COLUMNA AÑADIDA -->
                <th style="text-align: center">Estado</th>
                <th style="text-align: center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td style="text-align: center">{{ $item->id }}</td>
                    <td style="text-align: center">
                        <img src="{{ $item->display_image }}" alt="{{ $item->display_name }}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                    </td>
                    <td>{{ $item->display_name }}</td>
                    <td style="text-align: center">{{ $item->display_document }}</td>
                    <td style="text-align: center">
                        @if($item->birth_date)
                            {{ $item->formatted_birth_date }}<br><small>{{ $item->display_age }}</small>
                        @else
                            <small>Sin datos</small>
                        @endif
                    </td>
                    <td style="text-align: center">{{ $item->phone ?? 'SN' }}</td>
                    <td style="text-align: center">
                        {{ $item->ubicacion_segura }} <!-- ✅ MOSTRANDO LA UBICACIÓN -->
                    </td>
                    <td style="text-align: center">
                        <span class="label label-{{ $item->status == 1 ? 'success' : 'warning' }}">
                            {{ $item->estado_persona }}
                        </span>
                    </td>
                    <td class="text-right" style="width: 20%">
                        @can('view', $item)
                            <a href="{{ route('admin.people.show', $item) }}" title="Ver" class="btn btn-sm btn-warning">
                                <i class="voyager-eye"></i> Ver
                            </a>
                        @endcan
                        @can('update', $item)
                            <a href="{{ route('admin.people.edit', $item) }}" title="Editar" class="btn btn-sm btn-primary">
                                <i class="voyager-edit"></i> Editar
                            </a>
                        @endcan
                        @can('delete', $item)
                            <button type="button"
                                    class="btn btn-sm btn-danger"
                                    title="Borrar"
                                    onclick="deleteItem('{{ route('admin.people.destroy', $item) }}', '{{ $item->display_name }}', 'persona')"
                                    data-toggle="modal"
                                    data-target="#modal-delete">
                                <i class="voyager-trash"></i> Borrar
                            </button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9"> <!-- ✅ ACTUALIZADO: Cambiado de 8 a 9 columnas -->
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No hay resultados
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
        <nav class="text-right">
            {{ $data->appends(request()->only(['search','paginate']))->links() }}
        </nav>
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
