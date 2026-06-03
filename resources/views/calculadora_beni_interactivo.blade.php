@extends('layouts.app')

@section('title', 'Calculadora IDTGB - Beni')

@push('styles')
<style>
    :root { --verde-beni: #007A33; --verde-hover: #005f27; }
    .card-header-primary { background-color: var(--verde-beni); color: white; }
    .step-header { font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 5px; color: var(--verde-beni); }

    .boleta-container {
        border-left: 8px solid var(--verde-beni);
        background-color: #ffffff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 25px;
        border-radius: 8px;
        font-family: 'Courier New', Courier, monospace;
    }

    .categoria-card {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 12px 15px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
    }
    .categoria-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .categoria-card.selected {
        border-color: var(--verde-beni);
        background-color: #f0f9f4;
    }
    .categoria-card.selected::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 8px;
        right: 8px;
        color: var(--verde-beni);
        font-size: 0.9rem;
    }

    .categoria-tasa {
        font-size: 1.3rem;
        font-weight: bold;
        line-height: 1;
    }
    .categoria-tasa.linea-directa { color: #28a745; }
    .categoria-tasa.colateral { color: #d39e00; }
    .categoria-tasa.otros { color: #dc3545; }

    .categoria-icon {
        font-size: 1rem;
        margin-right: 8px;
    }
    .categoria-icon.linea-directa { color: #28a745; }
    .categoria-icon.colateral { color: #d39e00; }
    .categoria-icon.otros { color: #dc3545; }

    .categoria-desc {
        font-size: 0.75rem;
        color: #6c757d;
    }

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
                        <div class="step-header"><i class="fas fa-users me-2"></i> 2. Categoría de Parentesco</div>
                        <p class="text-muted small mb-3">Seleccione la categoría que mejor describa su relación con el transmitente:</p>
                        <div class="row g-2" id="categoria-selector">
                            @foreach($categorias as $cat)
                                <div class="col-12">
                                    <label class="categoria-card position-relative d-flex align-items-center" data-categoria="{{ $cat['key'] }}">
                                        <input type="radio" name="categoria_tasa" value="{{ $cat['key'] }}" required class="d-none">
                                        <div class="categoria-icon {{ $cat['grupo'] }}">
                                            <i class="fas {{ $cat['icono'] }}"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold">{{ $cat['label'] }}</span>
                                                <span class="categoria-tasa {{ $cat['grupo'] }}">{{ $cat['tasa'] }}%</span>
                                            </div>
                                            <div class="categoria-desc">{{ $cat['descripcion'] }}</div>
                                        </div>
                                    </label>
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
    const form = document.getElementById('form-calculadora');
    const resultadoDiv = document.getElementById('resultado');
    const boletaDetalle = document.getElementById('boleta-detalle');

    document.querySelectorAll('.categoria-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.categoria-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const categoriaSeleccionada = document.querySelector('input[name="categoria_tasa"]:checked');
        if (!categoriaSeleccionada) {
            alert('Por favor seleccione una categoría de parentesco');
            return;
        }

        resultadoDiv.classList.remove('d-none');
        boletaDetalle.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-success"></div><p class="mt-2">Calculando...</p></div>';

        try {
            const res = await fetch('{{ route("calculadora.beni.post") }}', {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Error en el cálculo');

            const fmt = (n) => parseFloat(n).toLocaleString('es-BO', { minimumFractionDigits: 2 });
            const fmtTotal = (n) => (Math.round(parseFloat(n) * 2) / 2).toLocaleString('es-BO', { minimumFractionDigits: 2 });

            boletaDetalle.innerHTML = `
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 border-start border-4 border-primary bg-light rounded">
                            <h6 class="text-uppercase small fw-bold">Referencia</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between"><span>Trámite:</span> <span class="fw-bold">${json.nro_tramite}</span></div>
                                <div class="d-flex justify-content-between"><span>Categoría:</span> <span class="fw-bold">${json.categoria_label}</span></div>
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
                            <div class="d-flex justify-content-between mb-2"><span>(+) Tributo Omitido (Actualizado)</span> <span class="fw-bold">Bs. ${fmt(json.tributo_actualizado)}</span></div>
                            <div class="d-flex justify-content-between mb-2"><span>(+) Interés Moratorio</span> <span class="fw-bold">Bs. ${fmt(json.interes)}</span></div>
                            <div class="d-flex justify-content-between mb-3"><span>(+) Multa IDF</span> <span class="fw-bold">Bs. ${fmt(json.multa_idf)}</span></div>
                            <div class="border-top border-2 border-dark pt-2 d-flex justify-content-between h4">
                                <span class="fw-bold">TOTAL DEUDA</span>
                                <span class="fw-bold text-success">Bs. ${fmtTotal(json.final)}</span>
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
