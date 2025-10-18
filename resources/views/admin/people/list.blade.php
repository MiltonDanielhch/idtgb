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
                <th style="text-align: center">Estado</th>
                <th style="text-align: center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                @php
                    $image = $item->image ? asset('storage/'.$item->image) : asset('images/default.jpg');
                    $fullName = $item->person_type === 'Jurídica'
                        ? $item->legal_name
                        : trim($item->first_name.' '.$item->middle_name.' '.$item->paternal_surname.' '.$item->maternal_surname);
                        if ($item->person_type === 'Jurídica') {
                            $doc = $item->nit ?: 'Sin NIT';
                        } else {
                            $doc = $item->ci ?: 'Sin CI';
                            if ($item->ci_complemento) $doc .= ' '.$item->ci_complemento;
                        }
                    $age = $item->birth_date ? \Carbon\Carbon::parse($item->birth_date)->age : '-';
                @endphp
                <tr>
                    <td style="text-align: center">{{ $item->id }}</td>
                    <td style="text-align: center">
                        <img src="{{ $image }}" alt="{{ $fullName }}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                    </td>
                    <td>{{ strtoupper($fullName) }}</td>
                    <td style="text-align: center">{{ $doc }}</td>
                    <td style="text-align: center">
                        @if($item->birth_date)
                            {{ \Carbon\Carbon::parse($item->birth_date)->format('d/m/Y') }}<br><small>{{ $age }} años</small>
                        @else
                            <small>Sin datos</small>
                        @endif
                    </td>
                    <td style="text-align: center">{{ $item->phone ?? 'SN' }}</td>
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
                            <form action="{{ route('admin.people.destroy', $item) }}" method="POST"
                                style="display: inline-block;"
                                onsubmit="return confirm('¿Borrar este registro?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Borrar">
                                    <i class="voyager-trash"></i> Borrar
                                </button>
                            </form>
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
        <nav class="text-right">
            {{ $data->appends(request()->only(['search','paginate']))->links() }}
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


{{-- <script>
    bindPageLinks();   // ← se ejecuta después de cargar el HTML via AJAX --}}
</script>
