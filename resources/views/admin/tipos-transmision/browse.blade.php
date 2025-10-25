@extends('voyager::master')

@section('page_title', 'Tipos de Transmisión')

@section('page_header')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel-heading">
                    <h1 class="page-title">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i> Tipos de Transmisión
                    </h1>
                    <a href="{{ route('admin.tipos-transmision.create') }}" class="btn btn-success btn-add-new">
                        <i class="voyager-plus"></i> <span>Añadir nuevo</span>
                    </a>
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
                            <div class="col-sm-10">
                                <div class="dataTables_length" id="dataTable_length">
                                    <label>Mostrar <select id="select-paginate" class="form-control input-sm"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> registros</label>
                                </div>
                            </div>
                            <div class="col-sm-2" style="padding-top: 20px"><input type="text" id="input-search" class="form-control" placeholder="Buscar..."></div>
                        </div>
                        <div id="div-results" style="min-height: 120px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('javascript')
        <script>
            $(document).ready(function() {
                let page = 1;
                let search = '';
                let paginate = 10;

                function fetch_data(page, search, paginate) {
                    $('#div-results').loading({message: 'Cargando...'});
                    $.ajax({
                        url: "{{ route('admin.tipos-transmision.ajax.list') }}",
                        data: { page, search, paginate },
                        success: function(data) {
                            $('#div-results').html(data);
                            $('#div-results').loading('toggle');
                        },
                        error: function() {
                            $('#div-results').loading('toggle');
                            toastr.error('Error al obtener los datos.');
                        }
                    });
                }

                fetch_data(page, search, paginate);

                $('#input-search').on('keyup', function() {
                    search = $(this).val();
                    fetch_data(1, search, paginate);
                });

                $('#select-paginate').on('change', function() {
                    paginate = $(this).val();
                    fetch_data(1, search, paginate);
                });

                $(document).on('click', '.pagination a', function(event) {
                    event.preventDefault();
                    page = $(this).attr('href').split('page=')[1];
                    fetch_data(page, search, paginate);
                });
            });
        </script>
    @endpush
@stop
