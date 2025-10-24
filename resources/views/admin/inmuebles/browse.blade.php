@extends('voyager::master')

@section('page_title', 'Inmuebles')

@section('page_header')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered" style="margin-bottom: 0;">
                    <div class="panel-body" style="padding: 0;">
                        <div class="col-md-8" style="padding: 0;">
                            <h1 class="page-title">
                                <i class="fa-solid fa-building"></i> Inmuebles
                            </h1>
                        </div>
                        <div class="col-md-4 text-right" style="margin-top: 30px;">
                            @can('create', App\Models\Inmueble::class)
                                <a href="{{ route('admin.inmuebles.create') }}" class="btn btn-success">
                                    <i class="voyager-plus"></i> Nuevo Inmueble
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
                                <div class="dataTables_length">
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
                            <div class="col-sm-3">
                                <input type="text" id="input-search" class="form-control" placeholder="🔍 Buscar catastro o dirección...">
                            </div>
                        </div>
                        <div class="row" id="div-results" style="min-height: 120px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.modal-delete')
@stop

@push('javascript')
<script>
    window.countPage = 10;

    window.list = function (page = 1) {
        $('#div-results').loading({message: 'Cargando...'});
        const url   = '{{ route("admin.inmuebles.ajax.list") }}';
        const search = $('#input-search').val() || '';

        $.get(url, {search, paginate: window.countPage, page})
         .done(res => {
             $('#div-results').html(res);
         })
         .fail(xhr => console.error(xhr))
         .always(() => $('#div-results').loading('toggle'));
    };

    $(function () {
        window.list();
        $('#input-search').on('keyup', e => { if (e.which === 13) window.list(); })
                         .on('input',  () => { clearTimeout(window.t); window.t = setTimeout(window.list, 500); });
        $('#select-paginate').change(function () { window.countPage = $(this).val(); window.list(); });
    });

    function deleteItem(url) { $('#delete_form').attr('action', url); }
</script>
@endpush
