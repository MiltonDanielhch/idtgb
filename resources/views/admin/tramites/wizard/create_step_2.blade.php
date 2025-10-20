{{-- resources/views/admin/tramites/wizard/create_step_2.blade.php --}}
@extends('admin.tramites.wizard.layout')

{{-- @push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
@endpush --}}

@push('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Fuerza el z-index del dropdown de Select2 para que aparezca sobre el modal */
        .select2-container--open {
            z-index: 9999 !important;
        }
        .select2-dropdown {
            z-index: 9999 !important;
        }
    </style>
@endpush

@section('page_title', 'Agregar Trámite - Paso 2')

@section('wizard-content')
<div class="panel panel-bordered">
    <div class="panel-body">
        <h4 class="text-muted">{{ $step_title }}</h4>
        <hr>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#searchPersonModal">
                    <i class="voyager-search"></i> Buscar y Añadir Disponente
                </button>
                <small class="text-muted" style="display: block; margin-top: 10px;">
                    Personas que transfieren el bien (donantes, causantes, etc.)
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Formulario principal --}}
<form action="{{ route('admin.tramites.wizard.post.step2') }}" method="POST">
    @csrf
    <div class="panel panel-bordered" style="margin-top: 20px;">
        <div class="panel-body">
            <h5>Disponentes en este Trámite</h5>

            @if($disponentes->count() > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Documento</th>
                            <th>Tipo</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($disponentes as $disponente)
                            <tr>
                                <td>{{ $disponente->display_name }}</td>
                                <td>{{ $disponente->display_document }}</td>
                                <td>{{ $disponente->person_type }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.tramites.wizard.remove.disponente', $disponente->id) }}"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Quitar a {{ $disponente->display_name }}?')">
                                        <i class="voyager-trash"></i> Quitar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-warning text-center">
                    <i class="voyager-warning"></i> No se han agregado disponentes.
                </div>
            @endif
        </div>

        <div class="panel-footer">
            <a href="{{ route('admin.tramites.wizard.create.step1') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Anterior
            </a>
            <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">
                <i class="voyager-x"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary pull-right" {{ $disponentes->count() == 0 ? 'disabled' : '' }}>
                Siguiente <i class="voyager-angle-right"></i>
            </button>
        </div>
    </div>
</form>

<!-- Modal de Búsqueda de Personas -->
<div class="modal fade" id="searchPersonModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="voyager-search"></i> Buscar Persona para Disponente
                </h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Buscar persona:</label>
                    <select id="person-select" class="form-control" style="width: 100%;">
                        <option value=""></option>
                    </select>
                </div>

                <div id="selected-person-info" style="display: none;">
                    <hr>
                    <h5>Persona seleccionada:</h5>
                    <div id="person-details" class="alert alert-info"></div>
                    <form id="add-disponente-form" action="{{ route('admin.tramites.wizard.add.disponente') }}" method="POST">
                        @csrf
                        <input type="hidden" name="person_id" id="selected-person-id">
                        <button type="submit" class="btn btn-success">
                            <i class="voyager-plus"></i> Agregar como Disponente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('javascript')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>

<script>
$(document).ready(function () {
    // Inicializar Select2 para búsqueda de personas
    function initializeSelect2() {
        $('#person-select').select2({
            theme: 'bootstrap',
            language: 'es',
            placeholder: 'Escriba nombre, CI o NIT para buscar...',
            minimumInputLength: 2,
            dropdownParent: $('#searchPersonModal'), // IMPORTANTE: Esto conecta el dropdown al modal
            ajax: {
                url: '{{ route("admin.tramites.wizard.ajax.personList") }}',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });
    }

    // Cuando se abre el modal, inicializar Select2
    $('#searchPersonModal').on('shown.bs.modal', function () {
        // Pequeño delay para asegurar que el modal esté completamente visible
        setTimeout(function() {
            initializeSelect2();
            // Enfocar el Select2 automáticamente cuando se abre el modal
            $('#person-select').select2('open');
        }, 100);
    });

    // Cuando se cierra el modal, limpiar y destruir Select2
    $('#searchPersonModal').on('hidden.bs.modal', function () {
        if ($('#person-select').hasClass('select2-hidden-accessible')) {
            $('#person-select').val(null).trigger('change');
            $('#person-select').select2('destroy');
        }
        $('#selected-person-info').hide();
    });

    // Cuando se selecciona una persona
    $(document).on('select2:select', '#person-select', function (e) {
        var data = e.params.data;
        $('#selected-person-id').val(data.id);

        // Mostrar información de la persona seleccionada
        $('#person-details').html(`
            <strong>Nombre:</strong> ${data.text}<br>
            <strong>Tipo:</strong> ${data.person_type || 'No especificado'}<br>
            <strong>Documento:</strong> ${data.document || 'No especificado'}
        `);
        $('#selected-person-info').show();
    });

    // Manejar envío del formulario de añadir disponente
    $('#add-disponente-form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#selected-person-id').val()) {
            alert('Por favor, seleccione una persona primero.');
            return;
        }

        // Mostrar loading
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="voyager-loading"></i> Agregando...').prop('disabled', true);

        // Enviar formulario via AJAX
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    // Cerrar modal y recargar página
                    $('#searchPersonModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'No se pudo agregar el disponente'));
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Error de servidor'));
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
