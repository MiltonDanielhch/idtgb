@extends('layouts.app')

@section('title', 'Calculadora IDTGB - Beni')

@push('styles')
<style>
    :root { --verde-beni: #007A33; --verde-hover: #005f27; }
    .card-header-primary { background-color: var(--verde-beni); color: white; }
    .step-header { font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 5px; color: var(--verde-beni); }

    /* Boleta de preliquidación con estilo de ticket */
    .boleta-container {
        border-left: 8px solid var(--verde-beni);
        background-color: #ffffff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 25px;
        border-radius: 8px;
        font-family: 'Courier New', Courier, monospace;
    }

    /* Optimización de Grupos de Parentesco */
    .parentesco-group {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: transform 0.2s;
    }
    .parentesco-group:hover { transform: translateY(-2px); }

    .parentesco-group-header {
        padding: 8px 12px;
        font-size: 0.9rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .linea-directa .parentesco-group-header { background: #d4edda; color: #155724; }
    .colateral .parentesco-group-header { background: #fff3cd; color: #856404; }
    .otros .parentesco-group-header { background: #f8d7da; color: #721c24; }

    .parentesco-group-body { padding: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

    .parentesco-option {
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        padding: 8px;
        border: 1px solid #eee;
        border-radius: 5px;
        cursor: pointer;
        margin: 0;
    }
    .parentesco-option.selected { background-color: #e7f3ff; border-color: #007bff; }
    .parentesco-option input { margin-right: 8px; }

    .tasa-badge { font-size: 0.75rem; margin-left: auto; padding: 2px 6px; border-radius: 10px; font-weight: bold; opacity: 0.8; }
</style>
@endpush

@section('content')
<div class="container py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Inicio</a></li>
            <li class="breadcrumb-item active">Calculadora IDTGB</li>
        </ol>
    </nav>

    <div class="card shadow-sm border-0">
        <div class="card-header card-header-primary p-3">
            <h5 class="mb-0"><i class="fas fa-calculator me-2"></i> Calculadora IDTGB - GAD BENI</h5>
        </div>

        <div class="card-body p-4">
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                <div><strong>Aviso Legal:</strong> Este cálculo es referencial basado en la Ley 812. El monto final se determina en ventanilla oficial.</div>
            </div>

            <form id="form-calculadora">
                @csrf
                <div class="row">
                    <div class="col-lg-7">
                        <div class="step-header"><i class="fas fa-id-card me-2"></i> 1. Información del Trámite</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">Nombres y Apellidos</label>
                                <input type="text" name="nombre_sujeto" class="form-control" placeholder="Nombre completo">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">C.I. / NIT</label>
                                <input type="text" name="ci_sujeto" class="form-control" placeholder="Documento">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Tipo de contribuyente</label>
                                <select name="tipo_contribuyente" class="form-select">
                                    <option value="Natural">Persona Natural (50 UFV multa)</option>
                                    <option value="Jurídica">Persona Jurídica (100 UFV multa)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Valor del Inmueble (Bs.)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs.</span>
                                    <input type="number" name="base_imponible" class="form-control" min="0.01" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Participación %</label>
                                <input type="number" name="participacion" class="form-control" value="100" min="1" max="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Tipo de Transmisión</label>
                                <select name="tipo_transmision" class="form-select">
                                    @foreach($tipos_transmision as $tipo)
                                        <option value="{{ $tipo->nombre }}">{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Fecha Transmisión</label>
                                <input type="date" name="fecha_transmision" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="step-header"><i class="fas fa-users me-2"></i> 2. Parentesco</div>
                        <div id="parentesco-selector">
                            @foreach($parentescosAgrupados as $grupoKey => $grupo)
                                <div class="parentesco-group {{ $grupoKey }}">
                                    <div class="parentesco-group-header">
                                        <i class="fas {{ $grupo['icono'] }}"></i>
                                        <span>{{ $grupo['label'] }} ({{ $grupo['tasa'] }}%)</span>
                                    </div>
                                    <div class="parentesco-group-body">
                                        @foreach($grupo['parentescos'] as $p)
                                            <label class="parentesco-option" data-group="{{ $grupoKey }}">
                                                <input type="radio" name="parentesco_id" value="{{ $p->id }}" required>
                                                <span>{{ $p->nombre }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg px-5 shadow-sm">
                        <i class="fas fa-calculator me-2"></i> Generar Preliquidación
                    </button>
                </div>
            </form>

            <div id="resultado" class="mt-5 d-none">
                <hr>
                <div class="step-header text-primary mt-4"><i class="fas fa-file-invoice-dollar me-2"></i> 3. Resultado Estimado</div>
                <div id="boleta-detalle"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // [Tu lógica original de JS aquí, solo ajustaré la presentación del HTML generado]
    const form = document.getElementById('form-calculadora');
    const resultadoDiv = document.getElementById('resultado');
    const boletaDetalle = document.getElementById('boleta-detalle');

    // Selección visual de parentescos
    document.querySelectorAll('.parentesco-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.parentesco-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        resultadoDiv.classList.remove('d-none');
        boletaDetalle.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-success"></div><p class="mt-2">Sintonizando cálculos...</p></div>';

        try {
            const res = await fetch('{{ route("calculadora.beni.post") }}', {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Error en el cálculo');

            const fmt = (n) => parseFloat(n).toLocaleString('es-BO', { minimumFractionDigits: 2 });

            boletaDetalle.innerHTML = `
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 border-start border-4 border-primary bg-light rounded">
                            <h6 class="text-uppercase small fw-bold">Referencia</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between"><span>Trámite:</span> <span class="fw-bold">${json.nro_tramite}</span></div>
                                <div class="d-flex justify-content-between"><span>UFV Venc:</span> <span>${json.ufv_vencimiento}</span></div>
                                <div class="d-flex justify-content-between"><span>UFV Pago:</span> <span>${json.ufv_pago}</span></div>
                                <div class="d-flex justify-content-between"><span>Días Mora:</span> <span>${json.dias_mora}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="boleta-container">
                            <div class="text-center mb-3">
                                <h5 class="mb-0">ESTADO DE CUENTA PRELIMINAR</h5>
                                <small>GAD BENI - ADMINISTRACIÓN TRIBUTARIA</small>
                            </div>
                            <div class="d-flex justify-content-between mb-2"><span>(+) Tributo Omitido</span> <span class="fw-bold">Bs. ${fmt(json.idtgb_base)}</span></div>
                            <div class="d-flex justify-content-between mb-2"><span>(+) Mantenimiento Valor</span> <span class="fw-bold">Bs. ${fmt(json.mantenimiento_valor)}</span></div>
                            <div class="d-flex justify-content-between mb-2"><span>(+) Interés Moratorio</span> <span class="fw-bold">Bs. ${fmt(json.interes)}</span></div>
                            <div class="d-flex justify-content-between mb-3"><span>(+) Multa IDF</span> <span class="fw-bold">Bs. ${fmt(json.multa_idf)}</span></div>
                            <div class="border-top border-2 border-dark pt-2 d-flex justify-content-between h4">
                                <span class="fw-bold">TOTAL DEUDA</span>
                                <span class="fw-bold text-success">Bs. ${fmt(json.final)}</span>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" onclick="descargarPDF()" class="btn btn-danger btn-lg shadow-sm">
                                <i class="fas fa-file-pdf me-2"></i> Descargar Reporte Oficial
                            </button>
                        </div>
                    </div>
                </div>
            `;
            resultadoDiv.scrollIntoView({ behavior: 'smooth' });
        } catch (err) {
            boletaDetalle.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        }
    });
});

function descargarPDF() {
    const form = document.getElementById('form-calculadora');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = '{{ route("calculadora.beni.pdf") }}?' + params;
}
</script>
@endpush
