# Plan de Redondeo a Entero (Sin Centavos)

## Contexto
En Bolivia no se usan monedas de centavos en el comercio diario. Por lo tanto, todos los montos monetarios deben redondearse al entero más cercano para facilitar el cambio en efectivo.

## Regla de Redondeo
- **0-49 centavos**: Redondear hacia abajo (.00)
- **50-99 centavos**: Redondear hacia arriba al siguiente entero (1.00)

## Fórmula
- **PHP**: `round($monto)` 
- **JavaScript**: `Math.round(monto)`

## Archivos y Ubicaciones Exactas

### 1. Vista de Lectura de Trámite
**Archivo**: `resources/views/admin/tramites/read.blade.php`
**Línea**: 111
**Código actual**: `{{ number_format(round($tramite->monto_final * 2) / 2, 2) }}`
**Código corregido**: `{{ number_format(round($tramite->monto_final), 0) }}`

### 2. Tabla de Trámites
**Archivo**: `resources/views/admin/tramites/list.blade.php`
**Línea**: 28
**Código actual**: `{{ number_format(round($t->monto_final * 2) / 2, 2) }}`
**Código corregido**: `{{ number_format(round($t->monto_final), 0) }}`

### 3. PDF A-01
**Archivo**: `resources/views/admin/tramites/pdf/a01.blade.php`
**Línea**: 74
**Código actual**: `{{ number_format(round($tramite->monto_final * 2) / 2, 2) }}`
**Código corregido**: `{{ number_format(round($tramite->monto_final), 0) }}`

### 4. Vista de Creación de Trámites Simplificados
**Archivo**: `resources/views/admin/tramites/simple/create.blade.php`
**Línea**: 382
**Código actual**: `$('#montoFinal').text('Bs. ' + (Math.round(resultados.final * 2) / 2).toFixed(2));`
**Código corregido**: `$('#montoFinal').text('Bs. ' + Math.round(resultados.final).toFixed(0));`

### 5. Vista de Edición de Trámites Simplificados
**Archivo**: `resources/views/admin/tramites/simple/edit.blade.php`
**Línea**: 386
**Código actual**: `$('#montoFinal').text('Bs. ' + (Math.round(resultados.final * 2) / 2).toFixed(2));`
**Código corregido**: `$('#montoFinal').text('Bs. ' + Math.round(resultados.final).toFixed(0));`

### 6. Dashboard - Últimos Trámites
**Archivo**: `resources/views/vendor/voyager/partials/dashboard-tramites-table.blade.php`
**Línea**: 27
**Código actual**: `{{ number_format(round($tramite['monto_final'] * 2) / 2, 2, ',', '.') }}`
**Código corregido**: `{{ number_format(round($tramite['monto_final']), 0, ',', '.') }}`

### 7. Reportes - Vista Index
**Archivo**: `resources/views/admin/reportes/index.blade.php`
**Línea**: 79
**Código actual**: `{{ number_format(round($tramite->monto_final * 2) / 2, 2, ',', '.') }}`
**Código corregido**: `{{ number_format(round($tramite->monto_final), 0, ',', '.') }}`

### 8. Calculadora IDTGB Beni
**Archivo**: `resources/views/calculadora_beni_interactivo.blade.php`
**Línea**: 233
**Código actual**: `const fmtTotal = (n) => (Math.round(parseFloat(n) * 2) / 2).toLocaleString('es-BO', { minimumFractionDigits: 2 });`
**Código corregido**: `const fmtTotal = (n) => Math.round(parseFloat(n)).toLocaleString('es-BO', { minimumFractionDigits: 0 });`

### 9. PDF de Reporte de Calculadora
**Archivo**: `resources/views/pdf/calculo_estimado_beni.blade.php`
**Línea**: 170
**Código actual**: `{{ number_format(round($final * 2) / 2, 2, ',', '.') }}`
**Código corregido**: `{{ number_format(round($final), 0, ',', '.') }}`

### 10. Pagos - Creación
**Archivo**: `resources/views/admin/tramites/pagos/create.blade.php`
**Líneas**: 33, 34
**Código actual (línea 33)**: `value="{{ old('monto', $tramite->monto_final) }}"`
**Código actual (línea 34)**: `Total a pagar: Bs. {{ number_format($tramite->monto_final, 2) }}`
**Código corregido (línea 33)**: `value="{{ old('monto', round($tramite->monto_final)) }}"`
**Código corregido (línea 34)**: `Total a pagar: Bs. {{ number_format(round($tramite->monto_final), 0) }}`

### 11. Formulario A-01 Interactivo
**Archivo**: `resources/views/pdf/form_a01_beni_interactivo.blade.php`
**Líneas**: 104, 118
**Código actual (línea 104)**: `{{ number_format($monto_final, 2, ',', '.') }}`
**Código actual (línea 118)**: `Bs. {{ number_format($monto_final, 2, ',', '.') }}`
**Código corregido (línea 104)**: `{{ number_format(round($monto_final), 0, ',', '.') }}`
**Código corregido (línea 118)**: `Bs. {{ number_format(round($monto_final), 0, ',', '.') }}`

### 12. Reportes - PDF Recaudación
**Archivo**: `resources/views/admin/reportes/pdf/recaudacion.blade.php`
**Línea**: 77
**Código actual**: `{{ number_format($tramite->monto_final, 2, ',', '.') }}`
**Código corregido**: `{{ number_format(round($tramite->monto_final), 0, ',', '.') }}`

## Resumen de Cambios
- **Total de archivos**: 12
- **Total de líneas a modificar**: 14
- **Tipo de cambios**: PHP (Blade) y JavaScript

## Pasos de Ejecución
1. Modificar cada archivo según las ubicaciones exactas indicadas
2. Reemplazar la fórmula de redondeo `round($monto * 2) / 2` por `round($monto)`
3. En JavaScript, reemplazar `Math.round(monto * 2) / 2` por `Math.round(monto)`
4. Ajustar el formato de número para mostrar 0 decimales en lugar de 2
5. Probar los cambios en todas las vistas afectadas

## Notas
- El redondeo se aplica solo a la visualización, no afecta los cálculos internos
- Los valores en la base de datos se mantienen con 2 decimales para precisión
- El redondeo se aplica en el momento de mostrar el monto al usuario
