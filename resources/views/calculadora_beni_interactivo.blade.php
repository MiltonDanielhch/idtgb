@extends('layouts.app')

@section('title', 'Calculadora IDTGB - Beni')

@push('styles')
<style>
    .disclaimer { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 6px; margin-top: 15px; }
    .card-header-primary { background-color: #007A33; color: white; } /* Color institucional Beni */
    .step-header { font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 5px; }
    .boleta-container { font-family: 'Courier New', Courier, monospace; background: #f9f9f9; padding: 20px; border: 1px solid #ccc; }
    .boleta-container {
        border-left: 10px solid #007A33; /* El verde del Beni */
        background-color: #fcfcfc;
        box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
        padding: 20px;
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
<div class="container py-2">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Calculá el IDTGB</li>
        </ol>
    </nav>
</div>

<div class="container pb-5">
    <div class="card shadow">
        <div class="card-header card-header-primary d-flex align-items-center">
            <i class="fas fa-calculator me-2"></i>
            <span>Calculá el Impuesto Departamental a la Transmisión Gratuita de Bienes (IDTGB)</span>
        </div>
        <div class="card-body">

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Atención:</strong> Este cálculo es <strong>estimado y referencial</strong>.
            </div>

            <form id="form-calculadora">
                @csrf

                <div class="step-header text-success"><i class="fas fa-id-card"></i> 1. Datos del Sujeto Pasivo</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-5">
                        <label class="form-label">Nombres y Apellidos</label>
                        <input type="text" name="nombre_sujeto" class="form-control" placeholder="Ej: MILTON ...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">C.I. / NIT</label>
                        <input type="text" name="ci_sujeto" class="form-control" placeholder="12345678">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Participación (%)</label>
                        <input type="number" name="participacion" class="form-control" value="100" min="1" max="100" required>
                        <div class="form-text">Porcentaje de la propiedad que se transmite.</div>
                    </div>
                </div>

                <div class="step-header text-success"><i class="fas fa-list-ol"></i> 2. Parámetros del Impuesto</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de contribuyente</label>
                        <select name="tipo_contribuyente" class="form-select" required>
                            <option value="Natural">Persona Natural</option>
                            <option value="Jurídica">Persona Jurídica</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valor del inmueble (Bs.)</label>
                        <input type="number" name="base_imponible" class="form-control" value="" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo de transmisión</label>
                        <select name="tipo_transmision" class="form-select" required>
                            @foreach($tipos_transmision as $tipo)
                                <option value="{{ $tipo->nombre }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha de transmisión</label>
                        <input type="date" name="fecha_transmision" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parentesco</label>
                        <select name="parentesco_id" class="form-select" required>
                            @foreach($parentescos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-play-circle"></i> Calcular Preliquidación
                    </button>
                </div>
            </form>

            <div id="resultado" class="mt-5 d-none">
                <div class="step-header text-primary"><i class="fas fa-chart-line"></i> 3. Resultado Estimado (Preliquidación)</div>
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

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        resultadoDiv.classList.remove('d-none');
        boletaDetalle.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-success"></div><p>Sintonizando cálculos...</p></div>';

        try {
            const res = await fetch('{{ route("calculadora.beni.post") }}', {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Error en el cálculo');

            const fmt = (n) => parseFloat(n).toLocaleString('es-BO', { minimumFractionDigits: 2 });

            boletaDetalle.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <div class="card bg-light border-0 p-3">
                            <h6>INFORMACIÓN DE REFERENCIA</h6>
                            <hr>
                            <p><strong>Nº TRÁMITE:</strong> <span class="text-primary">${json.nro_tramite}</span></p>
                            <p><strong>UFV VENCIMIENTO:</strong> ${json.ufv_vencimiento}</p>
                            <p><strong>UFV PAGO (HOY):</strong> ${json.ufv_pago}</p>
                            <p><strong>DÍAS MORA:</strong> ${json.dias_mora}</p>
                            <p><strong>PARTICIPACIÓN:</strong> ${json.participacion}%</p>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="boleta-container">
                            <div class="text-center"><strong>GAD BENI - PRELIQUIDACIÓN</strong></div>
                            <hr>
                            <div class="d-flex justify-content-between"><span>TRIBUTO OMITIDO (S900)</span> <strong>Bs. ${fmt(json.idtgb_base)}</strong></div>
                            <div class="d-flex justify-content-between"><span>MANT. DE VALOR (S920)</span> <strong>Bs. ${fmt(json.mantenimiento_valor)}</strong></div>
                            <div class="d-flex justify-content-between"><span>INTERÉS MORATORIO (S930)</span> <strong>Bs. ${fmt(json.interes)}</strong></div>
                            <div class="d-flex justify-content-between">
                                <span>
                                    MULTA POR IDF (S900)
                                    <small class="text-muted">(${json.tipo_contribuyente == 'Natural' ? '50' : '100'} UFV x ${json.ufv_pago})</small>
                                </span>
                                <strong>Bs. ${fmt(json.multa_idf)}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between h5"><span>TOTAL DEUDA</span> <strong>Bs. ${fmt(json.final)}</strong></div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" onclick="descargarPDF()" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> Imprimir PDF Oficial
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
