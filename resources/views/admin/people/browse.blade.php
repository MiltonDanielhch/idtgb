@extends('voyager::master')

@section('page_title', 'Personas')

@section('page_header')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered" style="margin-bottom: 0;">
                    <div class="panel-body" style="padding: 0;">
                        <div class="col-md-8" style="padding: 0;">
                            <h1 class="page-title">
                                <i class="voyager-person"></i> Personas
                            </h1>
                        </div>
                        <div class="col-md-4 text-right" style="margin-top: 30px;">
                            @can('create', App\Models\Person::class)
                                <a href="{{ route('admin.people.create') }}" class="btn btn-success">
                                    <i class="voyager-plus"></i> Nueva Persona
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-sm-9">
                                <div class="dataTables_length" id="dataTable_length">
                                    <label>Mostrar
                                        <select id="select-paginate" class="form-control input-sm">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select> registros
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3" style="margin-bottom: 10px">
                                <input type="text" id="input-search" placeholder="🔍 Buscar..." class="form-control" autocomplete="off">
                            </div>
                        </div>
                        <div class="row" id="div-results" style="min-height: 120px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal eliminar personalizado --}}
    @include('partials.modal-delete')
@stop

@section('css')
@stop

@push('javascript')
<script>
    let countPage = 10;
    let timeout   = null;

    // ✅ FUNCIÓN DELETEITEM MEJORADA - PARA USO GENERAL
    function deleteItem(url, itemName = null, itemType = 'registro') {
        // Actualizar la acción del formulario
        const deleteForm = document.getElementById('delete_form');
        if (deleteForm) {
            deleteForm.action = url;
        }

        // Resetear el formulario (limpiar campos anteriores)
        deleteForm.reset();

        // Actualizar mensajes dinámicos
        const deleteModalTitle = document.getElementById('delete_modal_title');
        const deleteModalMessage = document.getElementById('delete_modal_message');
        const deleteSubmitBtn = document.getElementById('delete_submit_btn');

        if (itemName) {
            // Si se proporciona un nombre específico
            if (deleteModalTitle) {
                deleteModalTitle.textContent = `¿Estás seguro que quieres eliminar ${itemType}?`;
            }
            if (deleteModalMessage) {
                deleteModalMessage.innerHTML = `¿Estás seguro que quieres eliminar <span style="color: #333;">${itemName}</span>?`;
            }
        } else {
            // Mensaje genérico
            if (deleteModalTitle) {
                deleteModalTitle.textContent = '¿Estás seguro que quieres eliminar?';
            }
            if (deleteModalMessage) {
                deleteModalMessage.textContent = `¿Estás seguro que quieres eliminar este ${itemType}?`;
            }
        }
    }

    $(document).ready(() => {
        list();

        $('#input-search').on('keyup', function (e) {
            if (e.keyCode === 13) {
                clearTimeout(timeout);
                list();
            }
        });

        $('#select-paginate').change(function () {
            countPage = $(this).val();
            list();
        });

        $('#input-search').on('input', function () {
            clearTimeout(timeout);
            timeout = setTimeout(list, 2000);
        });
    });

    function list(page = 1) {
        $('#div-results').loading({message: 'Cargando...'});

        let url   = '{{ route("admin.people.ajax.list") }}';
        let search = $('#input-search').val() ? $('#input-search').val() : '';

        $.ajax({
            url: `${url}?search=${search}&paginate=${countPage}&page=${page}`,
            type: 'get',
            success: function (result) {
                $("#div-results").html(result);
                $('#div-results').loading('toggle');
            },
            error: function (xhr) {
                $('#div-results').loading('toggle');
                console.error(xhr.responseText);
            }
        });
    }
</script>
@endpush
