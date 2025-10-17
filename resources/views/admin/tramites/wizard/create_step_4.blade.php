@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 4')

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
@endpush

@section('wizard-content')

<div class="panel panel-bordered">
    <div class="panel-body">
        <h4 class="text-muted">Paso 4: Identificación del Inmueble</h4>
        <hr>
        @if (!$tramite->inmueble)
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#searchInmuebleModal">
                <i class="voyager-search"></i> Buscar y Añadir Inmueble
            </button>
            <small class="text-muted" style="display: block; margin-top: 10px;">Seleccione el inmueble objeto de la transferencia.</small>
        @endif
    </div>
</div>

{{-- Formulario principal para avanzar al siguiente paso --}}
<form action="{{ route('admin.tramites.wizard.post.step4') }}" method="POST">
@csrf
    <div class="panel panel-bordered" style="margin-top: 20px;">
        <div class="panel-body">
            <h5>Inmueble en este Trámite</h5>
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($tramite->inmueble)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Dirección</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $tramite->inmueble->numero_matricula }}</td>
                            <td>{{ $tramite->inmueble->direccion }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.tramites.wizard.remove.inmueble') }}" class="btn btn-sm btn-danger">Quitar</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div class="text-center">
                    <p>Aún no se ha seleccionado un inmueble.</p>
                </div>
            @endif
        </div>

        <div class="panel-footer">
            <a href="{{ route('admin.tramites.wizard.create.step3') }}" class="btn btn-default">Anterior</a>
            <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">Cancelar</a>
            <button type="submit" class="btn btn-primary pull-right">Siguiente</button>
        </div>
    </div>
</form>

<!-- Modal de Búsqueda de Inmuebles -->
<div class="modal fade" id="searchInmuebleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Buscar Inmueble</h4>
            </div>
            <div class="modal-body">
                <table id="inmuebles-table" class="table table-hover" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Matrícula</th>
                            <th>Dirección</th>
                            <th>Superficie</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán vía AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('javascript')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        // Inicializar DataTable solo cuando el modal se muestra por primera vez
        $('#searchInmuebleModal').one('shown.bs.modal', function () {
            $('#inmuebles-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("admin.inmuebles.ajax.list") }}',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'numero_matricula', name: 'numero_matricula' },
                    { data: 'direccion', name: 'direccion' },
                    { data: 'superficie_terreno', name: 'superficie_terreno' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                },
                createdRow: function(row, data, dataIndex) {
                    var addButtonForm = `
                        <form action="{{ route('admin.tramites.wizard.add.inmueble') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="inmueble_id" value="${data.id}">
                            <button type="submit" class="btn btn-success btn-sm">Añadir</button>
                        </form>
                    `;
                    $('td:eq(4)', row).html(addButtonForm);
                }
            });
        });
    });
</script>
@endpush
