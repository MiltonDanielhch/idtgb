{{-- resources/views/admin/tramites/wizard/create_step_4.blade.php --}}
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

@section('page_title', 'Agregar Trámite - Paso 4')

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
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#searchInmuebleModal">
                    <i class="voyager-search"></i> Buscar y Añadir Inmueble
                </button>
                <small class="text-muted" style="display: block; margin-top: 10px;">
                    Inmueble(s) objeto de la transferencia.
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Formulario principal --}}
<form action="{{ route('admin.tramites.wizard.post.step4') }}" method="POST">
    @csrf
    <div class="panel panel-bordered" style="margin-top: 20px;">
        <div class="panel-body">
            <h5>Inmueble(s) en este Trámite</h5>

            @if($inmuebles->count() > 0)
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Catastro</th>
                            <th>Dirección</th>
                            <th>Tipo</th>
                            <th>Valor Catastral</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inmuebles as $inmueble)
                            <tr>
                                <td>{{ $inmueble->catastro }}</td>
                                <td>{{ $inmueble->direccion }}</td>
                                <td>{{ $inmueble->tipoInmueble->nombre ?? 'N/A' }}</td>
                                <td>Bs. {{ number_format($inmueble->valor_catastral, 2) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.tramites.wizard.remove.inmueble', $inmueble->id) }}"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Quitar inmueble {{ $inmueble->catastro }}?')">
                                        <i class="voyager-trash"></i> Quitar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-warning text-center">
                    <i class="voyager-warning"></i> No se han agregado inmuebles.
                </div>
            @endif
        </div>

        <div class="panel-footer">
            <a href="{{ route('admin.tramites.wizard.create.step3') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Anterior
            </a>
            <a href="{{ route('admin.tramites.wizard.cancel') }}" class="btn btn-danger">
                <i class="voyager-x"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary pull-right" {{ $inmuebles->count() == 0 ? 'disabled' : '' }}>
                Siguiente <i class="voyager-angle-right"></i>
            </button>
        </div>
    </div>
</form>

<!-- Modal de Búsqueda de Inmuebles -->
<div class="modal fade" id="searchInmuebleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="voyager-search"></i> Buscar Inmueble
                </h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Buscar inmueble:</label>
                    <select id="inmueble-select" class="form-control" style="width: 100%;">
                        <option value=""></option>
                    </select>
                </div>

                <div id="selected-inmueble-info" style="display: none;">
                    <hr>
                    <h5>Inmueble seleccionado:</h5>
                    <div id="inmueble-details" class="alert alert-info"></div>
                    <form id="add-inmueble-form" action="{{ route('admin.tramites.wizard.add.inmueble') }}" method="POST">
                        @csrf
                        <input type="hidden" name="inmueble_id" id="selected-inmueble-id">
                        <button type="submit" class="btn btn-success">
                            <i class="voyager-plus"></i> Agregar Inmueble
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
    // Función para inicializar Select2 de búsqueda de inmuebles
    function initializeInmuebleSelect() {
        $('#inmueble-select').select2({
            theme: 'bootstrap',
            language: 'es',
            placeholder: 'Escriba catastro, dirección o matrícula...',
            minimumInputLength: 2,
            dropdownParent: $('#searchInmuebleModal'), // CRÍTICO: Conectar al modal
            ajax: {
                url: '{{ route("admin.inmuebles.ajax.search") }}',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            }
        });
    }

    // Cuando se abre el modal, inicializar Select2
    $('#searchInmuebleModal').on('shown.bs.modal', function () {
        // Pequeño delay para asegurar que el modal esté completamente visible
        setTimeout(function() {
            initializeInmuebleSelect();
            // Ya no se abre automáticamente para evitar la sensación de "ya buscando"
            // $('#inmueble-select').select2('open');
        }, 100);
    });

    // Cuando se cierra el modal, limpiar y destruir Select2
    $('#searchInmuebleModal').on('hidden.bs.modal', function () {
        if ($('#inmueble-select').hasClass('select2-hidden-accessible')) {
            $('#inmueble-select').val(null).trigger('change');
            $('#inmueble-select').select2('destroy');
        }
        $('#selected-inmueble-info').hide();
        $('#selected-inmueble-id').val('');
    });

    // Cuando se selecciona un inmueble
    $(document).on('select2:select', '#inmueble-select', function (e) {
        var data = e.params.data;
        $('#selected-inmueble-id').val(data.id);

        // Formatear valor catastral
        var valorCatastral = 'N/A';
        if (data.valor_catastral) {
            valorCatastral = 'Bs. ' + parseFloat(data.valor_catastral).toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Mostrar información del inmueble seleccionado
        $('#inmueble-details').html(`
            <strong>Catastro:</strong> ${data.catastro || 'N/A'}<br>
            <strong>Dirección:</strong> ${data.direccion || 'N/A'}<br>
            <strong>Tipo:</strong> ${data.tipo_inmueble || 'N/A'}<br>
            <strong>Valor Catastral:</strong> ${valorCatastral}
        `);
        $('#selected-inmueble-info').show();
    });

    // Manejar envío del formulario de añadir inmueble
    $('#add-inmueble-form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#selected-inmueble-id').val()) {
            alert('Por favor, seleccione un inmueble primero.');
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
                // Si la petición AJAX tiene éxito (HTTP 2xx), recargamos la página.
                $('#searchInmuebleModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                var errorMessage = 'Error de servidor. No se pudo agregar el inmueble.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 422) {
                    errorMessage = 'Error de validación: ' + JSON.stringify(xhr.responseJSON.errors);
                } else if (xhr.status === 404) {
                    errorMessage = 'Ruta no encontrada. Verifique que la ruta de búsqueda exista.';
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
        var hasInmuebles = {{ $inmuebles->count() }} > 0;
        $('button[type="submit"]').prop('disabled', !hasInmuebles);
    });
});
</script>
@endpush
