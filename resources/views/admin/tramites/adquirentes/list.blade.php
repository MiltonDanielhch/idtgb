<div class="table-responsive">
    <table class="table table-voyager">
        <thead>
            <tr>
                <th>Persona</th>
                <th>CI</th>
                <th>Parentesco</th>
                <th class="text-center">%</th>
                <th class="text-center">Tasa</th>
                <th class="text-center">IDTGB Prop.</th>
                <th class="text-center">Exención</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td><strong>{{ $item->persona->fullName }}</strong></td>
                    <td>{{ $item->persona->ci }}</td>
                    <td>{{ $item->parentesco->nombre }}</td>
                    <td class="text-center">{{ $item->porcentaje }} %</td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $item->tasa_aplicada }} %</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">Bs. {{ number_format($item->idtgb_proporcional, 2) }}</span>
                    </td>
                    <td class="text-center">
                        @if($item->es_beneficiario_exencion)
                            <span class="label label-success">Sí</span>
                        @else
                            <span class="label label-default">No</span>
                        @endif
                    </td>
                    <td class="text-right" style="width: 12%">
                        @can('view', $item)
                            <a href="{{ route('admin.tramites.adquirentes.show', [$tramite, $item]) }}" class="btn btn-xs btn-warning" title="Ver">
                                <i class="voyager-eye"></i>
                            </a>
                        @endcan
                        @can('delete', $item)
                            <form action="{{ route('admin.tramites.adquirentes.destroy', [$tramite, $item]) }}" method="POST"
                                  style="display: inline-block;" onsubmit="return confirm('¿Quitar adquirente?')">
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
                    <td colspan="8">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No hay adquirentes registrados
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

<script>bindPageLinks();</script>
