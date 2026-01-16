{{-- resources/views/admin/tramites/wizard/create_step_3.blade.php --}}
@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 3')

@section('wizard-content')

{{-- Formulario para agregar adquirentes --}}
<div class="panel panel-bordered">
    <form action="{{ route('admin.tramites.wizard.add.adquirente') }}" method="POST" id="add-adquirente-form">
        @csrf
        <div class="panel-body">
            <h4 class="text-muted">{{ $step_title }}</h4>
            <p>Añada las personas que reciben el bien (herederos, donatarios, etc.) y su porcentaje de participación.</p>
            <hr>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0" style="list-style: none; padding-left: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <label>Adquirente <span class="required">*</span></label>
                    <select name="person_id" id="person_id_select" class="form-control" required></select>
                </div>
                <div class="col-md-3">
                    <label>Parentesco <span class="required">*</span></label>
                    <select name="parentesco_id" id="parentesco_id_select" class="form-control select2" required>
                        <option value="">Seleccione...</option>
                        @foreach($parentescos as $p)
                            <option value="{{ $p->id }}" data-tasa="{{ number_format($p->tasa_aplicable, 2) }}">
                                {{ $p->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="tasa_mostrada">Tasa Impuesto (%)</label>
                        <input type="text" id="tasa_mostrada" class="form-control" readonly style="background-color: #e9ecef; text-align: right;">
                    </div>
                </div>
                 <div class="col-md-2">
                    <label>Porcentaje del Bien (%) <span class="required">*</span></label>
                    <input type="number" name="porcentaje" class="form-control" step="0.01" min="0.01" max="100" placeholder="100.00" required style="text-align: right;">
                </div>
                <div class="col-md-1 text-right" style="padding-top: 25px;">
                    <button type="submit" class="btn btn-success"><i class="voyager-plus"></i> Añadir</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Tabla de adquirentes agregados y navegación --}}
<form action="{{ route('admin.tramites.wizard.post.step3') }}" method="POST">
    @csrf
    <div class="panel panel-bordered" style="margin-top: 20px;">
        <div class="panel-heading"><h3 class="panel-title">Adquirentes Agregados</h3></div>
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre Completo</th>
                        <th>Parentesco</th>
                        <th class="text-right">Porcentaje del Bien</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adquirentes as $adq)
                    <tr>
                        <td>{{ $adq->display_name }}</td>
                        <td>{{ $adq->parentesco_nombre }}</td>
                        <td class="text-right">{{ number_format($adq->porcentaje, 2) }} %</td>
                        <td class="text-right">
                            <a href="{{ route('admin.tramites.wizard.remove.adquirente', $adq->id) }}" class="btn btn-sm btn-danger">
                                <i class="voyager-trash"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">Aún no se han agregado adquirentes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-footer text-right">
            <a href="{{ route('admin.tramites.wizard.create.step2') }}" class="btn btn-default">
                <i class="voyager-angle-left"></i> Anterior
            </a>
            <button type="submit" class="btn btn-primary">
                Siguiente <i class="voyager-angle-right"></i>
            </button>
        </div>
    </div>
</form>
@endsection

@push('javascript')
<script>
document.addEventListener('DOMContentLoaded', function () {

    $('#person_id_select').select2({
        theme: 'bootstrap',
        placeholder: 'Buscar por nombre o CI...',
        ajax: {
            url: '{{ route('admin.tramites.wizard.ajax.personList') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    // Inicializar el select2 de parentesco
    const parentescoSelect = $('#parentesco_id_select');
    parentescoSelect.select2({
        theme: 'bootstrap'
    });

    // Escuchar el evento 'change' de select2 para mostrar la tasa
    const tasaInput = document.getElementById('tasa_mostrada');
    parentescoSelect.on('change', function (e) {
        const tasa = $(this).find(':selected').data('tasa');

        if (typeof tasa !== 'undefined') {
            tasaInput.value = parseFloat(tasa).toFixed(2);
        } else {
            tasaInput.value = '';
        }
    });

    // Calcular sumatoria total de porcentajes en la tabla
    const calcularTotal = () => {
        let total = 0;
        document.querySelectorAll('tbody tr td:nth-child(3)').forEach(td => {
            let valor = parseFloat(td.innerText.replace('%', '').trim());
            if(!isNaN(valor)) total += valor;
        });
        return total.toFixed(2);
    };

    // Puedes usar esto para mostrar una alerta si el total != 100 al intentar avanzar
    $('#add-adquirente-form').on('submit', function() {
        let actual = parseFloat(calcularTotal());
        let nuevo = parseFloat($('input[name="porcentaje"]').val());
        if((actual + nuevo) > 100) {
            alert('La suma total no puede exceder el 100%. Actualmente tiene ' + actual + '%');
            return false;
        }
    });
});
</script>
@endpush
