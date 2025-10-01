@extends('voyager::master')

@section('page_title', 'Parentescos')

@section('page_header')
    <div class="container-fluid">
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
                            <a href="{{ route('admin.parentescos.create') }}" class="btn btn-success">
                                <i class="voyager-plus"></i> <span>Crear</span>
                            </a>
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
</style>
@stop

@push('javascript')
<script>
    let countPage = 10;
    $(document).ready(function () {
        list();

        $('#input-search').on('keyup', function (e) {
            if (e.keyCode === 13) list();
        });
        $('#select-paginate').change(function () {
            countPage = $(this).val();
            list();
        });
    });

    function list(page = 1) {
        let url = '{{ url("admin/parentescos/ajax/list") }}';
        let search = $('#input-search').val() ? $('#input-search').val() : '';
        $.ajax({
            url: `${url}?search=${search}&paginate=${countPage}&page=${page}`,
            type: 'get',
            success: function (response) {
                $('#div-results').html(response);
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                $('#div-results').html('<div class="alert alert-danger">Error al cargar los datos</div>');
            }
        });
    }

    function deleteItem(url) {
        $('#delete_form').attr('action', url);
    }
</script>
@endpush
