<div class="table-responsive">
    <table class="table table-hover table-voyager">
        <thead>
            <tr>
                <th>ID</th>
                <th>Departamento</th>
                <th>Parentesco</th>
                <th>Tipo Transmisión</th>
                <th class="text-center">Tasa (%)</th>
                <th class="text-center">Vigente Desde</th>
                <th class="text-center">Vigente Hasta</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->departamento->nombre }}</td>
                    <td>{{ $t->parentesco->nombre }}</td>
                    <td>{{ $t->tipoTransmision->nombre ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge badge-primary">{{ number_format($t->tasa, 2) }}</span>
                    </td>
                    <td class="text-center">{{ $t->vigente_desde->format('d/m/Y') }}</td>
                    <td class="text-center">
                        {{ $t->vigente_hasta?->format('d/m/Y') ?? '-' }}
                    </td>
                    <td class="text-right" style="width: 18%">
                        @can('view', $t)
                            <a href="{{ route('admin.tasas.show', $t) }}" title="Ver" class="btn btn-sm btn-warning">
                                <i class="voyager-eye"></i> Ver
                            </a>
                        @endcan
                        @can('update', $t)
                            <a href="{{ route('admin.tasas.edit', $t) }}" class="btn btn-sm btn-primary">
                                <i class="voyager-edit"></i> Editar
                            </a>
                        @endcan
                         @can('delete', $t)
                             @php
                                 $desc = $t->departamento->nombre . ' / ' . $t->parentesco->nombre . ' / ' . number_format($t->tasa, 2) . '%';
                                 $url = route('admin.tasas.destroy', $t);
                             @endphp
                             <button type="button"
                                     class="btn btn-sm btn-danger"
                                     onclick="deleteItem('{{ $url }}', '{{ $desc }}')"
                                     data-toggle="modal"
                                     data-target="#modal-delete">
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
