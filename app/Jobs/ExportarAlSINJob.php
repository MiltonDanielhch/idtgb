<?php

namespace App\Jobs;

use App\Models\Tramite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExportarAlSINJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The tramite instance.
     *
     * @var \App\Models\Tramite
     */
    public $tramite;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Tramite $tramite
     * @return void
     */
    public function __construct(Tramite $tramite)
    {
        $this->tramite = $tramite;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info("Iniciando exportación a SIN para trámite: {$this->tramite->nro_tramite}");

        // 1. Cargar relaciones necesarias
        $this->tramite->load(['adquirentes.persona', 'disponentes.persona', 'inmuebles']);

        // 2. Generar contenido del CSV (ejemplo)
        $csvData = $this->generateCsvData();

        // 3. Guardar CSV en disco local (storage/app/sin_exports)
        $fileName = 'idtgb_' . $this->tramite->nro_tramite . '_' . now()->format('YmdHis') . '.csv';
        $filePath = 'sin_exports/' . $fileName;
        Storage::put($filePath, $csvData);

        Log::info("Archivo CSV generado: " . $filePath);

        // 4. Subir archivo por FTP (simulado)
        $this->uploadToFtp($filePath);
    }

    /**
     * Genera el contenido del archivo CSV.
     *
     * @return string
     */
    private function generateCsvData(): string
    {
        $tramite = $this->tramite;
        $inmueble = $tramite->inmuebles->first(); // Asumiendo un inmueble por trámite para simplicidad
        $adquirente = $tramite->adquirentes->first(); // Asumiendo un adquirente para simplicidad

        // Cabeceras (ajustar según especificación del SIN)
        $header = [
            'NRO_TRAMITE',
            'FECHA_PRESENTACION',
            'MONTO_FINAL',
            'ESTADO',
            'ADQUIRENTE_CI',
            'ADQUIRENTE_NOMBRE',
            'INMUEBLE_CATASTRO',
        ];

        // Datos
        $data = [
            $tramite->nro_tramite,
            $tramite->fecha_presentacion->format('Y-m-d'),
            $tramite->monto_final,
            $tramite->estado,
            $adquirente ? $adquirente->persona->nro_documento : '',
            $adquirente ? $adquirente->persona->nombre_completo : '',
            $inmueble ? $inmueble->catastro : '',
        ];

        $output = fopen('php://temp', 'w');
        fputcsv($output, $header);
        fputcsv($output, $data);
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Simula la subida del archivo a un servidor FTP.
     *
     * @param string $localPath
     * @return void
     */
    private function uploadToFtp(string $localPath)
    {
        $ftpHost = env('SIN_FTP_HOST');
        $ftpUser = env('SIN_FTP_USERNAME');
        $ftpPass = env('SIN_FTP_PASSWORD');
        $remotePath = env('SIN_FTP_REMOTE_PATH', '/');

        if (!$ftpHost || !$ftpUser || !$ftpPass) {
            Log::warning("Exportación a SIN: Credenciales FTP no configuradas. El archivo no será enviado.");
            return;
        }

        Log::info("Simulando subida de {$localPath} a FTP en {$ftpHost}");

        // try {
        //     Storage::disk('ftp_sin')->put($remotePath . basename($localPath), Storage::get($localPath));
        //     Log::info("Archivo subido a FTP exitosamente.");
        // } catch (\Exception $e) {
        //     Log::error("Error al subir archivo a FTP: " . $e->getMessage());
        // }
    }
}