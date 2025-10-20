{{-- resources/views/admin/tramites/wizard/create_step_3.blade.php --}}
@extends('admin.tramites.wizard.layout')

@push('css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Fuerza el z-index del dropdown de Select2 para que aparezca sobre el modal */
        .select2-container--open {
            z-index: 9999 !important;
        }
        .select2-dropdown {
            z-index: 9999 !important;
        }
        /* Asegurar que el modal tenga un z-index adecuado */
        .modal {
            z-index: 1050;
        }
        .modal-backdrop {
            z-index: 1040;
        }
    </style>
@endpush

@section('page_title', 'Agregar Trámite - Paso 3')

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
                    <i class="voyager-search"></i> Buscar y Añadir Adquirente
                </button>
                <small class="text-muted" style="display: block; margin-top: 10px;">
                    Personas que reciben el bien (herederos, donatarios, etc.)
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Formulario principal --}}
<form action="{{ route('admin.tramites.wizard.post.step3') }}" method="POST">
    @csrf
    <div class="panel panel-bordered" style="margin-top: 20px;">
        <div class="panel-body">
            <h5>Adquirentes en este Trámite</h5>

            @if($adquirentes->count() > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Documento</th>
                            <th>Parentesco</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adquirentes as $adquirente)
                            <tr>
                                <td>{{ $adquirente->display_name }}</td>
                                <td>{{ $adquirente->display_document }}</td>
                                <td>{{ $adquirente->parentesco_nombre ?? 'No especificado' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.tramites.wizard.remove.adquirente', $adquirente->id) }}"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Quitar a {{ $adquirente->display_name }}?')">
                                        <i class="voyager-trash"></i> Quitar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-warning text-center">
                    <i class="voyager-warning"></i> No se han agregado adquirentes.
                </div>
            @endif
        </div>

        <div class="panel-footer">
            <a href="{{ route('admin.tramites.wizard.create.step2') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Anterior
            </a>
            <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">
                <i class="voyager-x"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary pull-right" {{ $adquirentes->count() == 0 ? 'disabled' : '' }}>
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
                    <i class="voyager-search"></i> Buscar Persona para Adquirente
                </h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Parentesco <span class="required">*</span></label>
                    <select id="parentesco-select" class="form-control" style="width: 100%;" required>
                        <option value="">Seleccione un parentesco...</option>
                        @foreach($parentescos as $parentesco)
                            <option value="{{ $parentesco->id }}">{{ $parentesco->nombre }}</option>
                        @endforeach
                    </select>
                </div>

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
                    <form id="add-adquirente-form" action="{{ route('admin.tramites.wizard.add.adquirente') }}" method="POST">
                        @csrf
                        <input type="hidden" name="person_id" id="selected-person-id">
                        <input type="hidden" name="parentesco_id" id="selected-parentesco-id">
                        <button type="submit" class="btn btn-success">
                            <i class="voyager-plus"></i> Agregar como Adquirente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('javascript')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>

<script>
$(document).ready(function () {
    // Inicializar Select2 para parentesco (fuera del modal, no necesita dropdownParent)
    $('#parentesco-select').select2({
        theme: 'bootstrap',
        placeholder: 'Seleccione un parentesco...'
    });

    // Función para inicializar Select2 de búsqueda de personas
    function initializePersonSelect() {
        $('#person-select').select2({
            theme: 'bootstrap',
            language: 'es',
            placeholder: 'Escriba nombre, CI o NIT para buscar...',
            minimumInputLength: 2,
            dropdownParent: $('#searchPersonModal'), // CRÍTICO: Conectar al modal
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
            initializePersonSelect();
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
        $('#parentesco-select').val(null).trigger('change');
        $('#selected-person-info').hide();
        $('#selected-parentesco-id').val('');
        $('#selected-person-id').val('');
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

        // Verificar si ya tenemos parentesco para mostrar el formulario
        checkIfReadyToSubmit();
    });

    // Cuando se selecciona un parentesco
    $('#parentesco-select').on('change', function () {
        var parentescoId = $(this).val();
        var parentescoText = $(this).find('option:selected').text();
        $('#selected-parentesco-id').val(parentescoId);

        // Verificar si ya tenemos persona para mostrar el formulario
        checkIfReadyToSubmit();
    });

    // Función para verificar si podemos mostrar el formulario de envío
    function checkIfReadyToSubmit() {
        var hasPerson = $('#selected-person-id').val();
        var hasParentesco = $('#selected-parentesco-id').val();

        if (hasPerson && hasParentesco) {
            $('#selected-person-info').show();
        } else {
            $('#selected-person-info').hide();
        }
    }

    // Manejar envío del formulario de añadir adquirente
    $('#add-adquirente-form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#selected-person-id').val()) {
            alert('Por favor, seleccione una persona primero.');
            return;
        }

        if (!$('#selected-parentesco-id').val()) {
            alert('Por favor, seleccione un parentesco.');
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
                    alert('Error: ' + (response.message || 'No se pudo agregar el adquirente'));
                }
            },
            error: function(xhr) {
                var errorMessage = 'Error de servidor';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    errorMessage = 'Error de validación: ' + JSON.stringify(xhr.responseJSON.errors);
                }
                alert('Error: ' + errorMessage);
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Validar el botón principal del formulario
    $('form').on('keyup change', function() {
        var hasAdquirentes = {{ $adquirentes->count() }} > 0;
        $('button[type="submit"]').prop('disabled', !hasAdquirentes);
    });
});
</script>
@endpush
