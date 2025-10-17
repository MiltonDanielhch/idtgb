@extends('voyager::master')

@section('content')
<div class="page-content container-fluid">
    @include('voyager::alerts')

    <div class="row">
        <div class="col-md-12">

            <div class="panel panel-bordered">
                <div class="panel-body">
                    <p><strong>Paso {{ $current_step ?? 1 }} de {{ $total_steps ?? 5 }}:</strong> {{ $step_title ?? 'Inicio' }}</p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" role="progressbar"
                             aria-valuenow="{{ $progress ?? 20 }}" aria-valuemin="0" aria-valuemax="100" style="width:{{ $progress ?? 20 }}%">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Aquí se cargará el contenido de cada paso --}}
            @yield('wizard-content')

        </div>
    </div>
</div>
@stop
