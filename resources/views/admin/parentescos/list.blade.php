<div class="col-md-12">
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parentescos as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td class="text-right" style="width: 40%">
                        @can('view', $p)
                            <a href="{{ route('admin.parentescos.show', $p) }}" title="Ver" class="btn btn-sm btn-warning">
                                <i class="voyager-eye"></i> Ver
                            </a>
                        @endcan
                        @can('update', $p)
                            <a href="{{ route('admin.parentescos.edit', $p) }}" title="Editar" class="btn btn-sm btn-primary">
                                <i class="voyager-edit"></i> Editar
                            </a>
                        @endcan
                        @can('delete', $p)
                            <button type="button"
                                    class="btn btn-sm btn-danger"
                                    title="Borrar"
                                    onclick="deleteItem('{{ route('admin.parentescos.destroy', $p) }}', '{{ $p->nombre }}')"
                                    data-toggle="modal"
                                    data-target="#delete_modal">
                                <i class="voyager-trash"></i> Borrar
                            </button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3">
                        <h5 class="text-center" style="margin-top: 50px">
                            <img src="{{ asset('images/empty.png') }}" width="120px" alt="" style="opacity: 0.8">
                            <br><br>
                            No se encontraron parentescos
                        </h5>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="col-md-12">
    <div class="col-md-4 text-muted">
        @if($parentescos->count())
            Mostrando del {{ $parentescos->firstItem() }} al {{ $parentescos->lastItem() }} de {{ $parentescos->total() }} registros.
        @endif
    </div>
    <div class="col-md-8 text-right">
        <nav class="text-right">
            {{ $parentescos->links() }}
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


@extends('voyager::master')

@section('page_title', 'Parentescos')

@section('page_header')
    <div class="container-fluid">
        @include('voyager::alerts')

        {{-- Mostrar mensajes de éxito/error --}}
        @if(session('message'))
            <div class="alert alert-{{ session('alert-type', 'info') }} alert-dismissible auto-dismiss">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                {{ session('message') }}
            </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered" style="margin-bottom: 0;">
                    <div class="panel-body" style="padding: 0;">
                        <div class="col-md-8" style="padding: 0;">
                            <h1 class="page-title">
                                <i class="voyager-people"></i> Parentescos
                            </h1>
                        </div>
                        <div class="col-md-4 text-right" style="margin-top: 30px;">
                               <!-- Botón crear -->
                                @can('create', App\Models\Parentesco::class)
                                    <a href="{{ route('admin.parentescos.create') }}" class="btn btn-success">
                                        <i class="voyager-plus"></i> Nuevo Parentesco
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
                        <div class="col-sm-9" style="margin-bottom: 0">
                            <div class="dataTables_length" id="dataTable">
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
                        <div class="col-sm-3" style="margin-bottom: 0">
                            <input type="text" id="input-search" class="form-control" placeholder="Buscar...">
                            <br>
                        </div>
                    </div>
                    {{-- resultados via AJAX --}}
                    <div class="row" id="div-results" style="min-height: 120px"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal eliminar --}}
<div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="voyager-trash"></i> ¿Desea eliminar este parentesco?</h4>
            </div>
            <div class="modal-footer">
                <form action="#" id="delete_form" method="POST">
                    @method('DELETE') @csrf
                    <input type="submit" class="btn btn-danger pull-right delete-confirm" value="Sí, eliminar">
                </form>
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .select2-container{width:100%!important}
    .badge{font-size:100%}
    .badge-success{background-color:#28a745}
    .badge-primary{background-color:#007bff}
    .badge-warning{background-color:#ffc107;color:#212529}
    .badge-danger{background-color:#dc3545}

    /* Animación suave para el loading */
    .loading-icon {
        animation: spin 1.5s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@stop

@push('javascript')
<script>
    let countPage = 10;

    $(document).ready(function () {
        // ========== AUTO-DISMISS ALERTS ==========
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            $('.auto-dismiss').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);

        // También permitir cerrar manualmente
        $('.auto-dismiss .close').click(function(e) {
            e.preventDefault();
            $(this).closest('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        });

        // ========== INITIAL LOAD ==========
        list();

        // ========== SEARCH EVENTS ==========
        $('#input-search').on('keyup', function (e) {
            if (e.keyCode === 13) {
                list(1); // Reset to page 1 when searching
            }
        });

        // Search on input change with debounce (opcional)
        let searchTimeout;
        $('#input-search').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                list(1);
            }, 500);
        });

        // ========== PAGINATION SELECT ==========
        $('#select-paginate').change(function () {
            countPage = $(this).val();
            list(1); // Reset to page 1 when changing items per page
        });
    });

    function deleteItem(url, nombre) {
        $('#delete_form').attr('action', url);
        $('.modal-title').html('<i class="voyager-trash"></i> ¿Eliminar el parentesco "<strong>' + nombre + '</strong>"?');
    }

    // Función mejorada para manejar errores de AJAX
    function list(page = 1) {
        let url = '{{ url("admin/parentescos/ajax/list") }}';
        let search = $('#input-search').val() ? $('#input-search').val().trim() : '';

        // Mostrar loading
        $('#div-results').html(`
            <div class="text-center" style="padding: 40px">
                <i class="voyager-refresh voyager-2x loading-icon"></i>
                <br>Cargando...
            </div>
        `);

        $.ajax({
            url: `${url}?search=${encodeURIComponent(search)}&paginate=${countPage}&page=${page}`,
            type: 'get',
            success: function (response) {
                $('#div-results').html(response);
            },
            error: function (xhr) {
                console.error('Error:', xhr.responseText);
                $('#div-results').html(`
                    <div class="alert alert-danger text-center">
                        <i class="voyager-warning"></i><br>
                        Error al cargar los datos.<br>
                        <button onclick="list(${page})" class="btn btn-xs btn-default mt-2">Reintentar</button>
                    </div>
                `);
            }
        });
    }
</script>
@endpush
