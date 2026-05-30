<div class="col-md-12">
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Departamento</th>
                    <th>Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feriados as $f)
                <tr>
                    <td>{{ $f->id }}</td>
                    <td>{{ $f->fecha->format('d/m/Y') }}</td>
                    <td>{{ $f->nombre }}</td>
                    <td>
                        @php
                        $badgeClass = match($f->tipo) {
                            'Nacional' => 'primary',
                            'Departamental' => 'warning',
                            'Municipal' => 'info',
                            default => 'secondary'
                        };
                        @endphp
                        <span class="badge badge-{{ $badgeClass }}">{{ $f->tipo }}</span>
                    </td>
                    <td>{{ $f->departamento ? $f->departamento->nombre : 'Nacional' }}</td>
                    <td>
                        @if($f->activo)
                            <span class="badge badge-success">Activo</span>
                        @else
                            <span class="badge badge-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-right" style="width: 30%">
                        <a href="{{ route('admin.feriados.show', $f) }}" title="Ver" class="btn btn-sm btn-warning">
                            <i class="voyager-eye"></i> Ver
                        </a>
                        <a href="{{ route('admin.feriados.edit', $f) }}" title="Editar" class="btn btn-sm btn-primary">
                            <i class="voyager-edit"></i> Editar
                        </a>
                        <button type="button"
                                class="btn btn-sm btn-danger"
                                title="Borrar"
                                onclick="deleteItem('{{ route('admin.feriados.destroy', $f) }}', '{{ $f->nombre }}')"
                                data-toggle="modal"
                                data-target="#delete_modal">
                            <i class="voyager-trash"></i> Borrar
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <h5 class="text-center" style="margin-top: 50px">
                            <i class="voyager-calendar voyager-3x" style="opacity: 0.3"></i>
                            <br><br>
                            No se encontraron feriados
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
        @if($feriados->count())
            Mostrando del {{ $feriados->firstItem() }} al {{ $feriados->lastItem() }} de {{ $feriados->total() }} registros.
        @endif
    </div>
    <div class="col-md-8 text-right">
        <nav class="text-right">
            {{ $feriados->links() }}
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

            if (typeof list === 'function') {
                list(page);
            } else {
                window.location.href = $(this).attr('href');
            }
        });
    });
</script>
@endif
