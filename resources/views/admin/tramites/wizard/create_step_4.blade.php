{{-- resources/views/admin/tramites/wizard/create_step_4.blade.php --}}
@extends('admin.tramites.wizard.layout')

@push('css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--open {
            z-index: 9999 !important;
        }
        .select2-dropdown {
            z-index: 9999 !important;
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
                <button type="button" class="btn btn-primary" id="toggleSearchPanel">
                    <i class="voyager-search"></i> Buscar y Añadir Inmueble
                </button>
                <small class="text-muted" style="display: block; margin-top: 10px;">
                    Inmueble(s) objeto de la transferencia.
                </small>
            </div>
        </div>

        <div id="searchInmueblePanel" class="panel panel-default" style="margin-top: 20px; display: none;">
            <div class="panel-body">
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
@endsection

@push('javascript')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/i18n/es.js"></script>

<script>
$(document).ready(function () {
    var select2Initialized = false;

    $('#toggleSearchPanel').on('click', function () {
        var $panel = $('#searchInmueblePanel');
        if ($panel.is(':hidden')) {
            $panel.slideDown(300);
            if (!select2Initialized) {
                initializeInmuebleSelect();
                select2Initialized = true;
            }
        } else {
            $panel.slideUp(300);
        }
    });

    function initializeInmuebleSelect() {
        $('#inmueble-select').select2({
            theme: 'bootstrap',
            language: 'es',
            placeholder: 'Escriba catastro, dirección o matrícula...',
            minimumInputLength: 2,
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

    $(document).on('select2:select', '#inmueble-select', function (e) {
        var data = e.params.data;
        $('#selected-inmueble-id').val(data.id);

        var valorCatastral = 'N/A';
        if (data.valor_catastral) {
            valorCatastral = 'Bs. ' + parseFloat(data.valor_catastral).toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        $('#inmueble-details').html(`
            <strong>Catastro:</strong> ${data.catastro || 'N/A'}<br>
            <strong>Dirección:</strong> ${data.direccion || 'N/A'}<br>
            <strong>Tipo:</strong> ${data.tipo_inmueble || 'N/A'}<br>
            <strong>Valor Catastral:</strong> ${valorCatastral}
        `);
        $('#selected-inmueble-info').show();
    });

    $('#add-inmueble-form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#selected-inmueble-id').val()) {
            alert('Por favor, seleccione un inmueble primero.');
            return;
        }

        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="voyager-loading"></i> Agregando...').prop('disabled', true);

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
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
});
</script>
@endpush
