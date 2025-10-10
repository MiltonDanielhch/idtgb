<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal Ciudadano | IDTGB - Beni</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --beni-green: #007A33; --beni-green-light: #00A652; --beni-yellow: #FCD116; }
        body { background-color: #f4f7fa; font-family: "Segoe UI", Arial, sans-serif; }
        .navbar-custom { background: linear-gradient(135deg, var(--beni-green), var(--beni-green-light)); }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link { color: #fff !important; }
        .card-header-primary { background: linear-gradient(135deg, var(--beni-green), var(--beni-green-light)); color: #fff; }
        .btn-primary { background-color: var(--beni-green); border-color: var(--beni-green); }
        .btn-primary:hover { background-color: var(--beni-green-light); border-color: var(--beni-green-light); }
        .btn-outline-warning { border-color: #ffc107; color: #856404; }
        .btn-outline-warning:hover { background-color: #ffc107; color: #212529; }
        .step-header { font-weight: 600; font-size: 1.1rem; margin-bottom: 1rem; }
        .result-box { background: #e9f5ff; border-left: 5px solid var(--beni-green); }
        .final-amount { font-size: 1.75rem; font-weight: 700; color: var(--beni-green); }
        .icon-size { font-size: 1.2rem; margin-right: 0.4rem; }
        .disclaimer { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 6px; margin-top: 15px; }
    </style>
</head>
<body>

<!-- HEADER -->
<nav class="navbar navbar-expand-lg navbar-custom shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-building"></i> Portal Ciudadano - GAD Beni</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="#"><i class="fas fa-home"></i> Inicio</a>
        </div>
    </div>
</nav>

<!-- BREADCRUMB -->
<div class="container py-2">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Calculá el IDTGB</li>
        </ol>
    </nav>
</div>

<!-- CONTENIDO -->
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
                    <label class="form-label">Tipo de contribuyente</label>
                    <select name="tipo_contribuyente" class="form-select" required>
                        <option value="">--Seleccione--</option>
                        <option value="Natural">Natural</option>
                        <option value="Jurídica">Jurídica</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Inmueble (catastro Beni)</label>
                    <select name="inmueble_id" class="form-select" required>
                        <option value="">--Seleccione--</option>
                        @foreach($inmuebles as $inm)
                            <option value="{{ $inm->id }}">
                                {{ $inm->catastro }} - {{ $inm->direccion }}, {{ $inm->municipio->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo de transmisión</label>
                    <select name="tipo_transmision" class="form-select" required>
                        <option value="Entre vivos">Entre vivos (Donación)</option>
                        <option value="Testamento">Sucesión (Herencia/Testamento)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha de transmisión</label>
                    <input type="date" name="fecha_transmision" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Base imponible (Bs.)</label>
                    <input type="number" name="base_imponible" class="form-control" value="1000" min="0.01" step="0.01" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Parentesco / Alicuota</label>
                    <select name="parentesco_id" class="form-select" required>
                        <option value="">--Seleccione--</option>
                        @foreach($parentescos as $p)
                            @php
                                $tasa = $p->tasa_vigente ?? 0.00;
                            @endphp
                            <option value="{{ $p->id }}" data-tasa="{{ $tasa }}">
                                {{ $p->nombre }} ({{ number_format($tasa, 2) }}%)
                            </option>
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
        <div class="card-footer text-muted text-center">
            <small>Este cálculo es referencial y no reemplaza el trámite oficial ante la GAD-Beni.</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

            resultado.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Fecha de transmisión:</strong> ${json.fecha_transmision}</p>
                        <p><strong>Fecha de vencimiento:</strong> ${json.fecha_vencimiento}</p>
                        <p><strong>Base imponible:</strong> Bs. ${parseFloat(json.base_imponible).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                        <p><strong>Tasa aplicada:</strong> ${json.tasa}%</p>
                        <p><strong>UFV:</strong> ${json.ufv}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>IDTGB estimado:</strong> Bs. ${parseFloat(json.tributo_omitido).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                        <p class="final-amount">Monto estimado: Bs. ${parseFloat(json.monto_final).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
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

</body>
</html>
