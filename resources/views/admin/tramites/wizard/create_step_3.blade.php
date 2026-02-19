{{-- resources/views/admin/tramites/wizard/create_step_3.blade.php --}}
@extends('admin.tramites.wizard.layout')

@section('page_title', 'Agregar Trámite - Paso 3')

@section('wizard-styles')
<style>
    /* Estilos para grupos de parentesco */
    .parentesco-group {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        margin-bottom: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .parentesco-group:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .parentesco-group-header {
        padding: 12px 15px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .parentesco-group.linea-directa .parentesco-group-header {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border-bottom: 2px solid #28a745;
    }
    .parentesco-group.colateral .parentesco-group-header {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
        color: #856404;
        border-bottom: 2px solid #ffc107;
    }
    .parentesco-group.otros .parentesco-group-header {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
        border-bottom: 2px solid #dc3545;
    }
    .parentesco-group-body {
        padding: 15px;
        background: #fff;
    }
    .parentesco-option {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        margin-bottom: 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #e9ecef;
    }
    .parentesco-option:hover {
        background-color: #f8f9fa;
        border-color: #adb5bd;
    }
    .parentesco-option input[type="radio"] {
        margin-right: 10px;
        width: 18px;
        height: 18px;
    }
    .parentesco-option.selected {
        background-color: #e7f3ff;
        border-color: #007bff;
    }
    .tasa-badge {
        margin-left: auto;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .tasa-badge.linea-directa {
        background-color: #d4edda;
        color: #155724;
    }
    .tasa-badge.colateral {
        background-color: #fff3cd;
        color: #856404;
    }
    .tasa-badge.otros {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>
@endsection

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
                <div class="col-md-5">
                    <label>Parentesco <span class="required">*</span> <span class="text-muted">(seleccione grupo y tipo)</span></label>
                    <div id="parentesco-selector">
                        @foreach($parentescosAgrupados as $grupoKey => $grupo)
                            <div class="parentesco-group {{ $grupoKey }}">
                                <div class="parentesco-group-header">
                                    <i class="voyager-{{ $grupoKey === 'linea-directa' ? 'person' : ($grupoKey === 'colateral' ? 'people' : 'user') }}"></i>
                                    <span>{{ $grupo['label'] }}</span>
                                </div>
                                <div class="parentesco-group-body">
                                    @foreach($grupo['parentescos'] as $p)
                                        <label class="parentesco-option" data-group="{{ $grupoKey }}" data-tasa="{{ number_format($p->tasa_aplicable, 2) }}">
                                            <input type="radio" name="parentesco_id" value="{{ $p->id }}" required>
                                            <span>{{ $p->nombre }}</span>
                                            <span class="tasa-badge {{ $grupoKey }}">{{ number_format($p->tasa_aplicable, 2) }}%</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="tasa_mostrada">Tasa Impuesto (%)</label>
                        <input type="text" id="tasa_mostrada" class="form-control" readonly style="background-color: #e9ecef; text-align: right;">
                    </div>
                     <div class="form-group">
                        <label for="porcentaje">Porcentaje del Bien (%) <span class="required">*</span></label>
                        <input type="number" name="porcentaje" id="porcentaje" class="form-control" step="0.01" min="0.01" max="100" placeholder="100.00" required style="text-align: right;">
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-footer text-right">
            <button type="submit" class="btn btn-success"><i class="voyager-plus"></i> Añadir Adquirente</button>
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
            url: "{{ route('admin.tramites.wizard.ajax.personList') }}",
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

    // Manejo de selección de parentescos
    const parentescoOptions = document.querySelectorAll('.parentesco-option');
    const tasaInput = document.getElementById('tasa_mostrada');
    
    parentescoOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remover selección previa
            parentescoOptions.forEach(opt => opt.classList.remove('selected'));
            // Marcar como seleccionado
            this.classList.add('selected');
            // Seleccionar el radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Actualizar la tasa mostrada
            const tasa = this.getAttribute('data-tasa');
            if (tasa) {
                tasaInput.value = parseFloat(tasa).toFixed(2);
            } else {
                tasaInput.value = '';
            }
        });
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

    // Validar que no se exceda el 100% al agregar
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
