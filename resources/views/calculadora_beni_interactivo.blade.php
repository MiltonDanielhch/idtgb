@extends('layouts.app')

@section('title', 'Calculadora IDTGB - Beni')

@push('styles')
<style>
    .disclaimer { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 6px; margin-top: 15px; }
</style>
@endpush

@section('content')
<!-- BREADCRUMB -->
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
            <i class="fas fa-calculator icon-size"></i>
            <span>Calculá el Impuesto Departamental a la Transmisión Gratuita de Bienes (IDTGB)</span>
        </div>
        <div class="card-body">

            <!-- ADVERTENCIA IMPORTANTE -->
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Atención:</strong> Este cálculo es <strong>estimado y orientativo</strong>.
                El monto final puede variar según avalúos, exenciones y normativa vigente.
                Próximamente podrá iniciar un trámite oficial directamente desde esta plataforma.
            </div>

            <!-- PARÁMETROS -->
            <div class="step-header"><i class="fas fa-list-ol text-primary"></i> Parámetros</div>
            <form id="form-calculadora" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label for="tipo_contribuyente" class="form-label">Tipo de contribuyente</label>
                    <select id="tipo_contribuyente" name="tipo_contribuyente" class="form-select" required>
                        <option value="">--Seleccione--</option>
                        <option value="Natural">Natural</option>
                        <option value="Jurídica">Jurídica</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="base_imponible" class="form-label">Valor del inmueble (Bs.)</label>
                    <input type="number" id="base_imponible" name="base_imponible" class="form-control" value="100000" min="0.01" step="0.01" required>
                    <div class="form-text">Ingrese el valor catastral o comercial del inmueble.</div>
                </div>

                <div class="col-md-6">
                    <label for="tipo_transmision" class="form-label">Tipo de transmisión</label>
                    <select id="tipo_transmision" name="tipo_transmision" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        @foreach($tipos_transmision as $tipo)
                            <option value="{{ $tipo->nombre }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="fecha_transmision" class="form-label">Fecha de transmisión</label>
                    <input type="date" id="fecha_transmision" name="fecha_transmision" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="parentesco_id" class="form-label">Parentesco</label>
                    <select id="parentesco_id" name="parentesco_id" class="form-select" required>
                        <option value="">--Seleccione--</option>
                        @foreach($parentescos as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Seleccione el parentesco para aplicar la alícuota del Beni.</div>
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play-circle"></i> Calcular
                    </button>
                </div>
            </form>

            <!-- TOTAL DETERMINADO -->
            <div class="step-header mt-4"><i class="fas fa-chart-line text-success"></i> Resultado estimado</div>
            <div id="resultado" class="result-box p-3 rounded d-none"></div>

            <!-- MARCO LEGAL -->
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-gavel"></i> Marco Legal:
                    <a href="#" class="text-decoration-none">Ley Departamental IDTGB - Beni</a>
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-calculadora');
    const resultado = document.getElementById('resultado');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        resultado.classList.remove('d-none');
        resultado.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Calculando...</div>';

        try {
            const res = await fetch('/calculadora-idtgb-beni', {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();

            if (!res.ok) {
                throw new Error(json.message || 'Error en el cálculo');
            }

            // Formateador de moneda
            const fmt = (num) => parseFloat(num || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Variables seguras (evita NaN)
            const tributoOmitido = json.idtgb_base || json.idtgb || 0;
            const mantValor = json.mantenimiento_valor || 0;
            const intereses = json.interes || 0;
            const multa = json.multa_idf || 0;
            const sancion = json.sancion_omision_pago || 0;
            const totalDeuda = json.final || 0;
            const diasMora = json.dias_mora || 0;

            // Determinar encabezado y estilo según mora
            let headerHtml = '';
            let alertClass = 'alert-secondary';

            if (diasMora > 0) {
                headerHtml = `<h6 class="alert-heading fw-bold text-danger"><i class="fas fa-clock"></i> FECHA DE PAGO FUERA DE PLAZO (${diasMora} días de mora)</h6>`;
                alertClass = 'alert-danger';
            } else {
                headerHtml = `<h6 class="alert-heading fw-bold text-success"><i class="fas fa-check-circle"></i> CÁLCULO DENTRO DE PLAZO</h6>`;
                alertClass = 'alert-success';
            }

            // Construcción de la Boleta (Diseño solicitado)
            const boletaHtml = `
                <div class="alert ${alertClass} mt-3">
                    ${headerHtml}
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Tributo Omitido (S900)....................................................</span>
                        <span class="fw-bold">Bs. ${fmt(tributoOmitido)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Mantenimiento de Valor (S920).......................................</span>
                        <span class="fw-bold">Bs. ${fmt(mantValor)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Intereses Moratorios (S930).............................................</span>
                        <span class="fw-bold">Bs. ${fmt(intereses)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Multa por Incumplimiento Deberes Formales (S900)......</span>
                        <span class="fw-bold">Bs. ${fmt(multa)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Sanción por Omisión al Pago (S940)................................</span>
                        <span class="fw-bold">Bs. ${fmt(sancion)}</span>
                    </div>
                    <hr>
                    <div class="text-end mb-2">
                        <small class="text-muted">LEY DEPTAL. Nº 1097 vigente desde el 10/04/2023</small>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-primary">
                        <span>Total Deuda......................................................................</span>
                        <span>Bs. ${fmt(totalDeuda)}</span>
                    </div>
                </div>
            `;

            resultado.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Fecha de transmisión:</strong> ${json.fecha_transmision}</p>
                        <p><strong>Fecha de vencimiento:</strong> ${json.fecha_vencimiento}</p>
                        <p><strong>Valor del inmueble:</strong> Bs. ${fmt(json.base_imponible || json.base)}</p>
                        <p><strong>Tasa aplicada:</strong> ${json.tasa || 0}%</p>
                        <p><strong>UFV de Pago:</strong> ${json.ufv_aplicada || 'No disponible'}</p>
                    </div>
                    <div class="col-md-6">
                        ${boletaHtml}
                    </div>
                </div>
                <div class="disclaimer mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Este resultado es únicamente orientativo.</strong>
                    Para un trámite válido, acérquese a las oficinas de la Gobernación del Beni.
                </div>
                <div class="text-end mt-3">
                    <button class="btn btn-outline-warning btn-sm" id="download-pdf">
                        <i class="fas fa-file-pdf"></i> Descargar cálculo estimado
                    </button>
                </div>
            `;
        } catch (err) {
            resultado.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
            console.error(err);
        }
    });

    // Descarga PDF del cálculo estimado
    resultado.addEventListener('click', (e) => {
        if (e.target.id === 'download-pdf') {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append('download_pdf', '1');

            fetch('/calculadora-idtgb-beni-pdf', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            })
            .then(res => res.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'IDTGB_Beni_Calculo_Estimado.pdf';
                document.body.appendChild(a);
                a.click();
                a.remove();
            })
            .catch(err => {
                alert('Error al generar PDF: ' + err.message);
                console.error(err);
            });
        }
    });
});
</script>
@endpush
