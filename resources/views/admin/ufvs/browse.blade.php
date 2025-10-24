@extends('voyager::master')

@section('page_title', 'Valores UFV - IDTGB Beni')

@section('page_header')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered" style="margin-bottom: 0;">
                    <div class="panel-body" style="padding: 0;">
                        <div class="col-md-8" style="padding: 0;">
                            <h1 class="page-title">
                                <i class="voyager-calendar"></i> Valores UFV - IDTGB Beni
                            </h1>
                        </div>
                        <div class="col-md-4 text-right" style="margin-top: 30px;">
                            @can('create', App\Models\Ufv::class)
                                <a href="{{ route('admin.ufvs.create') }}" class="btn btn-success">
                                    <i class="voyager-plus"></i> Agregar UFV
                                </a>
                                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modal-csv">
                                    <i class="voyager-upload"></i> Importar CSV
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal importar CSV --}}
    <div class="modal fade" id="modal-csv" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.ufvs.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Importar UFVs desde CSV</h4>
                    </div>
                    <div class="modal-body">
                        <label>Archivo CSV (fecha,valor)</label>
                        <input type="file" name="archivo" class="form-control" accept=".csv" required>
                        <small>Formato: YYYY-MM-DD,123.45678</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Importar</button>
                    </div>
                </div>
            </form>
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
                                <input type="text" id="input-search" class="form-control" placeholder="🔍 Buscar fecha (YYYY-MM-DD)...">
                            </div>
                        </div>
                        <div class="row" id="div-results" style="min-height: 120px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('javascript')
<script>
    window.countPage = 10;

    window.list = function (page = 1) {
        $('#div-results').loading({message: 'Cargando...'});
        const url   = '{{ route("admin.ufvs.ajax.list") }}';
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
</script>
@endpush
