@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 3')

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
@endpush

@section('wizard-content')

{{-- Botón para abrir el modal de búsqueda --}}
<div class="panel panel-bordered">
    <div class="panel-body">
        <h4 class="text-muted">Paso 3: Identificación de Adquirentes</h4>
        <hr>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#searchPersonModal">
            <i class="voyager-search"></i> Buscar y Añadir Adquirente
        </button>
        <small class="text-muted" style="display: block; margin-top: 10px;">Añada todas las personas que están recibiendo el bien.</small>
    </div>
</div>


{{-- Formulario principal para avanzar al siguiente paso --}}
<form action="{{ route('admin.tramites.wizard.post.step3') }}" method="POST">
@csrf
    <div class="panel panel-bordered" style="margin-top: 20px;">
        <div class="panel-body">
            {{-- Lista de Adquirentes Agregados --}}
            <h5>Adquirentes en este Trámite</h5>
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre Completo</th>
                        <th>C.I.</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adquirentes as $adquirente)
                        <tr>
                            <td>{{ $adquirente->full_name }}</td>
                            <td>{{ $adquirente->ci }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.tramites.wizard.remove.adquirente', ['person_id' => $adquirente->id]) }}" class="btn btn-sm btn-danger">Quitar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">Aún no se han agregado adquirentes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel-footer">
            <a href="{{ route('admin.tramites.wizard.create.step2') }}" class="btn btn-default">Anterior</a>
            <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">Cancelar</a>
            <button type="submit" class="btn btn-primary pull-right">Siguiente</button>
        </div>
    </div>
</form>

<!-- Modal de Búsqueda de Personas -->
<div class="modal fade" id="searchPersonModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Buscar Persona</h4>
            </div>
            <div class="modal-body" style="padding-top: 0;">
                <div class="form-group" style="margin-top: 15px;">
                    <label for="parentesco_id_modal">Parentesco <span class="text-danger">*</span></label>
                    <select id="parentesco_id_modal" class="form-control" required>
                        <option value="" disabled selected>Seleccione un parentesco...</option>
                        @php
                            // Asumimos que los parentescos se pueden obtener así.
                            // Si no, deberás pasarlos desde el controlador.
                            $parentescos = \App\Models\Parentesco::all();
                        @endphp
                        @foreach($parentescos as $parentesco)
                            <option value="{{ $parentesco->id }}">{{ $parentesco->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-body">
                <table id="people-table" class="table table-hover" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>CI</th>
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
{{-- Select2 es necesario para los desplegables con búsqueda --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        // Inicializar DataTable solo cuando el modal se muestra por primera vez
        $('#searchPersonModal').one('shown.bs.modal', function () {
            // Inicializamos Select2 para el desplegable de parentesco DENTRO del modal
            $('#parentesco_id_modal').select2({
                dropdownParent: $('#searchPersonModal') // MUY IMPORTANTE: para que la búsqueda funcione dentro del modal
            });

            $('#people-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("admin.people.datatable") }}',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'full_name', name: 'full_name' },
                    { data: 'ci', name: 'ci' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                createdRow: function(row, data, dataIndex) {
                    var parentescoId = $('#parentesco_id_modal').val();
                    if (!parentescoId) {
                        // Deshabilitar el botón si no se ha seleccionado parentesco
                        $('td:eq(3)', row).html('<button type="button" class="btn btn-success btn-sm" disabled>Añadir</button><p><small class="text-danger">Seleccione un parentesco</small></p>');
                        return;
                    }

                    // El controlador AJAX para personas no devuelve una columna 'action', la creamos aquí.
                    var addButtonForm = `
                        <form action="{{ route('admin.tramites.wizard.add.adquirente') }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="parentesco_id" value="${parentescoId}">
                            <input type="hidden" name="person_id" value="${data.id}">
                            <button type="submit" class="btn btn-success btn-sm">Añadir</button>
                        </form>
                    `;
                    $('td:eq(3)', row).html(addButtonForm);
                }
            });

            // Volver a dibujar la tabla cuando cambia el parentesco para actualizar los botones "Añadir"
            $('#parentesco_id_modal').on('change', function() {
                $('#people-table').DataTable().draw(false);
            });
        });
    });
</script>
@endpush