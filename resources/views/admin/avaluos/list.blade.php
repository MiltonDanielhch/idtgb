<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Inmueble (Catástro)</th>
                <th>Tipo</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Valor (Bs)</th>
                <th>Perito</th>
                <th class="text-center">Doc</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $a)
                <tr>
                    <td><strong>{{ $a->inmueble->catastro }}</strong></td>
                    <td>{{ $a->tipo_avaluo }}</td>
                    <td class="text-center">{{ $a->fecha_avaluo->format('d/m/Y') }}</td>
                    <td class="text-center">
                        <span class="badge badge-primary">Bs. {{ number_format($a->valor, 2) }}</span>
                    </td>
                    <td>
                        @if($a->perito)
                            {{ $a->perito->nombre_completo }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($a->documento_path)
                            <a href="{{ route('admin.avaluos.download', $a) }}" class="btn btn-xs btn-info" title="Descargar">
                                <i class="voyager-download"></i>
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $a->estado == 'Vigente' ? 'success' : 'danger' }}">
                            {{ $a->estado }}
                        </span>
                    </td>
                    <td class="text-right" style="width: 18%">
                        @can('view', $a)
                            <a href="{{ route('admin.avaluos.show', $a) }}" class="btn btn-sm btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                        @can('update', $a)
                            <a href="{{ route('admin.avaluos.edit', $a) }}" class="btn btn-sm btn-primary" title="Editar">
                                <i class="voyager-edit"></i>
                            </a>
                        @endcan
                        @can('delete', $a)
                            <form action="{{ route('admin.avaluos.destroy', $a) }}" method="POST"
                                  style="display: inline-block;" onsubmit="return confirm('¿Borrar este avalúo?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Borrar">
                                    <i class="voyager-trash"></i>
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
                            No hay avalúos registrados
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
        <nav>{{ $data->appends(request()->only(['search', 'paginate', 'inmueble_id']))->links() }}</nav>
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
