<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\CalculadoraBeniController;
use App\Http\Controllers\RoleController;
use TCG\Voyager\Facades\Voyager;
use App\Http\Controllers\ParentescoController;
use App\Http\Controllers\TasaController;
use App\Http\Controllers\ExencionController;
use App\Http\Controllers\InmuebleController;
use App\Http\Controllers\AvaluoController;
use App\Http\Controllers\TramiteController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\TramiteExencionController;
use App\Http\Controllers\AdquirenteTramiteController;
use App\Http\Controllers\DisponenteTramiteController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\TramiteInmuebleController;
use App\Http\Controllers\UfvController;
use App\Http\Controllers\ValidacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\Admin\TramiteWizardController;
use App\Http\Controllers\Admin\TramiteSimpleController;
use App\Http\Controllers\TipoInmuebleController;
use App\Http\Controllers\TipoTransmisionController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Redirección raíz y login
Route::redirect('login', 'admin/login')->name('login');
Route::get('/', function () {
    return view('home');
});

// La vista 'home' ahora es manejada por el CalculadoraBeniController para unificar el punto de entrada.
// Route::get('/', [CalculadoraBeniController::class, 'formulario'])->name('home');

// -------------------------------------------------------------------------
// RUTAS PÚBLICAS (Sin autenticación)
// -------------------------------------------------------------------------
Route::get('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'formulario'])->name('calculadora.beni.form'); // Mantenida por si hay enlaces directos
// Cambia esto:
Route::post('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'calcular'])->name('calculadora.beni.post'); // Cambiado de .calcular a .post
Route::get('/calculadora-idtgb-beni-pdf', [CalculadoraBeniController::class, 'descargarPdf'])->name('calculadora.beni.pdf'); // Cambiado de POST a GET (para descarga directa)
    
Route::get('/validar/{hash}', [ValidacionController::class, 'show'])->name('tramite.validar');

// Ruta para mostrar el tutorial del ciudadano
Route::get('/tutorial-ciudadano', function () {
    // Lee el contenido del archivo Markdown
    $markdownContent = File::get(resource_path('views/doc/usu/# 2.md'));
    // Convierte Markdown a HTML
    $htmlContent = Str::markdown($markdownContent);
    // Muestra el contenido en una vista de layout simple
    return view('public.tutorial_layout', ['content' => $htmlContent]);
})->name('doc.ciudadano.tutorial');

// -------------------------------------------------------------------------
// GRUPO PRINCIPAL DE ADMINISTRACIÓN (Con autenticación y middleware)
// -------------------------------------------------------------------------
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {

    // Rutas de Voyager (Panel de administración)
    Voyager::routes();

    // Sobrescribir la ruta del dashboard de Voyager
    Route::get('/', [DashboardController::class, 'index'])->name('voyager.dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'fetchData'])->name('admin.dashboard.data');

    // ──────────────── REPORTES ────────────────
    Route::prefix('reportes')->name('admin.reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
    });

    // ──────────────── RECURSOS PRINCIPALES (CRUD Estándar) ────────────────
    // Se usa Route::resource para generar todas las rutas CRUD (index, create, store, show, edit, update, destroy)
    // de forma automática, limpiando el código.
    // El método ->names() asegura que los nombres de las rutas sigan el patrón 'admin.recurso.accion'.
    // El método ->parameters() corrige el nombre del parámetro de ruta (ej. {person} en lugar de {people}).

    Route::resource('people', PersonController::class)->names('admin.people')->parameters(['people' => 'person']);
    Route::get('people/ajax/list', [PersonController::class, 'list'])->name('admin.people.ajax.list');

    Route::resource('parentescos', ParentescoController::class)->names('admin.parentescos');
    Route::get('parentescos/ajax/list', [ParentescoController::class, 'list'])->name('admin.parentescos.ajax.list');

    Route::resource('tipos-transmision', TipoTransmisionController::class)->names('admin.tipos-transmision')->parameters(['tipos-transmision' => 'tipoTransmision']);
    Route::get('tipos-transmision/ajax/list', [TipoTransmisionController::class, 'list'])->name('admin.tipos-transmision.ajax.list');

    Route::resource('exenciones', ExencionController::class)->names('admin.exenciones')->parameters(['exenciones' => 'exencion']);
    Route::get('exenciones/ajax/list', [ExencionController::class, 'list'])->name('admin.exenciones.ajax.list');

    Route::resource('tipos-inmueble', TipoInmuebleController::class)->names('admin.tipos-inmueble')->parameters(['tipos-inmueble' => 'tipoInmueble']);
    Route::get('tipos-inmueble/ajax/list', [TipoInmuebleController::class, 'list'])->name('admin.tipos-inmueble.ajax.list');

    Route::resource('tasas', TasaController::class)->names('admin.tasas');
    Route::get('tasas/ajax/list', [TasaController::class, 'list'])->name('admin.tasas.ajax.list');

    Route::resource('inmuebles', InmuebleController::class)->names('admin.inmuebles');
    Route::get('inmuebles/ajax/list', [InmuebleController::class, 'list'])->name('admin.inmuebles.ajax.list');

    Route::get('inmuebles/ajax/search', [InmuebleController::class, 'ajaxSearch'])->name('admin.inmuebles.ajax.search');

    Route::resource('avaluos', AvaluoController::class)->names('admin.avaluos');
    Route::get('avaluos/ajax/list', [AvaluoController::class, 'list'])->name('admin.avaluos.ajax.list');
    Route::get('avaluos/{avaluo}/download', [AvaluoController::class, 'download'])->name('admin.avaluos.download');

    // ──────────────── TRÁMITES ────────────────
    // Trámite simplificado (una sola página) - ÚNICA FORMA DE CREAR TRÁMITES
    Route::prefix('tramites/simple')->name('admin.tramites.simple.')->group(function () {
        Route::get('create', [TramiteSimpleController::class, 'create'])->name('create');
        Route::post('store', [TramiteSimpleController::class, 'store'])->name('store');
        Route::get('ajax/persons', [TramiteSimpleController::class, 'ajaxPersonList'])->name('ajax.persons');
        Route::post('ajax/calculate', [TramiteSimpleController::class, 'ajaxCalculate'])->name('ajax.calculate');
    });

    // Ruta personalizada que debe ir ANTES que el resource para no ser capturada por el método show del resource.
    Route::get('tramites/{tramite}/a01', [TramiteController::class, 'a01'])->name('admin.tramites.a01');

    // El resource se mantiene para las rutas show, edit, update, destroy.
    // Los métodos create y store se excluyen porque ahora los maneja el TramiteSimpleController.
    Route::resource('tramites', TramiteController::class)->names('admin.tramites')->except(['create', 'store']);
    // Redirigimos la ruta de creación al formulario simplificado.
    Route::get('tramites/create', fn() => redirect()->route('admin.tramites.simple.create'))->name('admin.tramites.create');
    Route::get('tramites/ajax/list', [TramiteController::class, 'list'])->name('admin.tramites.ajax.list');

    // ──────────────── RECURSOS ANIDADOS (Pivotes de Trámite) ────────────────
    // Para los recursos anidados, se mantiene la definición explícita porque es muy clara y
    // permite un control total sobre los parámetros (ej. {item}) y las rutas específicas.
    // Se estandariza la ruta de listado AJAX a '/ajax/list'.

    Route::prefix('tramites/{tramite}/inmuebles')->name('admin.tramites.inmuebles.')->group(function () {
        Route::get('/', [TramiteInmuebleController::class, 'index'])->name('index');
        Route::get('/ajax/list', [TramiteInmuebleController::class, 'list'])->name('ajax.list');
        Route::get('/create', [TramiteInmuebleController::class, 'create'])->name('create');
        Route::post('/', [TramiteInmuebleController::class, 'store'])->name('store');
        Route::get('/{item}', [TramiteInmuebleController::class, 'show'])->name('show');
        Route::delete('/{item}', [TramiteInmuebleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/exenciones')->name('admin.tramites.exenciones.')->group(function () {
        Route::get('/', [TramiteExencionController::class, 'index'])->name('index');
        Route::get('/ajax/list', [TramiteExencionController::class, 'list'])->name('ajax.list');
        Route::get('/create', [TramiteExencionController::class, 'create'])->name('create');
        Route::post('/', [TramiteExencionController::class, 'store'])->name('store');
        Route::get('/{item}', [TramiteExencionController::class, 'show'])->name('show');
        Route::delete('/{item}', [TramiteExencionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/adquirentes')->name('admin.tramites.adquirentes.')->group(function () {
        Route::get('/', [AdquirenteTramiteController::class, 'index'])->name('index');
        Route::get('/ajax/list', [AdquirenteTramiteController::class, 'list'])->name('ajax.list');
        Route::get('/create', [AdquirenteTramiteController::class, 'create'])->name('create');
        Route::post('/', [AdquirenteTramiteController::class, 'store'])->name('store');
        Route::get('/{item}', [AdquirenteTramiteController::class, 'show'])->name('show');
        Route::delete('/{item}', [AdquirenteTramiteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/disponentes')->name('admin.tramites.disponentes.')->group(function () {
        Route::get('/', [DisponenteTramiteController::class, 'index'])->name('index');
        Route::get('/ajax/list', [DisponenteTramiteController::class, 'list'])->name('ajax.list');
        Route::get('/create', [DisponenteTramiteController::class, 'create'])->name('create');
        Route::post('/', [DisponenteTramiteController::class, 'store'])->name('store');
        Route::get('/{item}', [DisponenteTramiteController::class, 'show'])->name('show');
        Route::delete('/{item}', [DisponenteTramiteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/documentos')->name('admin.tramites.documentos.')->group(function () {
        Route::get('/', [DocumentoController::class, 'index'])->name('index');
        Route::get('/ajax/list', [DocumentoController::class, 'list'])->name('ajax.list');
        Route::get('/create', [DocumentoController::class, 'create'])->name('create');
        Route::post('/', [DocumentoController::class, 'store'])->name('store');
        Route::get('/{item}', [DocumentoController::class, 'show'])->name('show');
        Route::delete('/{item}', [DocumentoController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/pagos')->name('admin.tramites.pagos.')->group(function () {
        Route::get('/', [PagoController::class, 'index'])->name('index');
        Route::get('/ajax/list', [PagoController::class, 'list'])->name('ajax.list');
        Route::get('/create', [PagoController::class, 'create'])->name('create');
        Route::post('/', [PagoController::class, 'store'])->name('store');
        Route::get('/{pago}', [PagoController::class, 'show'])->name('show');
        Route::delete('/{pago}', [PagoController::class, 'destroy'])->name('destroy');
    });

    // ──────────────── OTROS RECURSOS Y UTILIDADES ────────────────
    Route::prefix('ufvs')->name('admin.ufvs.')->group(function () {
        Route::get('/', [UfvController::class, 'index'])->name('index');
        Route::get('/list', [UfvController::class, 'list'])->name('ajax.list');
        Route::get('/create', [UfvController::class, 'create'])->name('create');
        Route::post('/', [UfvController::class, 'store'])->name('store');
        Route::post('/import', [UfvController::class, 'import'])->name('import');
        Route::get('/{ufv}', [UfvController::class, 'show'])->name('show');
        Route::get('/{ufv}/edit', [UfvController::class, 'edit'])->name('edit');
        Route::put('/{ufv}', [UfvController::class, 'update'])->name('update');
        Route::delete('/{ufv}', [UfvController::class, 'destroy'])->name('destroy');
    });

    // ──────────────── USUARIOS Y ROLES (Extensión de Voyager) ────────────────
    Route::prefix('users')->group(function () {
        Route::get('/ajax/list', [UserController::class, 'list'])->name('voyager.users.ajax.list');
        Route::post('/store', [UserController::class, 'store'])->name('voyager.users.store');
        Route::put('/{id}', [UserController::class, 'update'])->name('voyager.users.update');
        Route::delete('/{id}/deleted', [UserController::class, 'destroy'])->name('voyager.users.destroy');
    });

    Route::prefix('roles')->group(function () {
        Route::get('/ajax/list', [RoleController::class, 'list'])->name('voyager.roles.ajax.list');
    });

    // ──────────────── AJAX GENÉRICO ────────────────
    Route::prefix('ajax')->group(function () {
        Route::get('/personList', [AjaxController::class, 'personList']);
        Route::post('/person/store', [AjaxController::class, 'personStore']);
    });

    // ──────────────── UTILIDADES ────────────────
    // Movido a un controlador para permitir el cacheo de rutas en producción
    Route::get('/clear-cache', function() {
        Artisan::call('optimize:clear');
        return redirect('/admin/profile')->with([
            'message'    => 'Cache eliminada.',
            'alert-type' => 'success',
        ]);
    })->name('admin.clear.cache');
});

// ──────────────── SERVIDOR DE ARCHIVOS DIRECTO (Windows fix) ────────────────
// Ruta alternativa para servir archivos cuando los enlaces simbólicos no funcionan
Route::get('/storage/{path}', function ($path) {
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $file = Storage::disk('public')->get($path);
    $fullPath = storage_path('app/public/' . $path);
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fullPath);

    return response($file, 200, [
        'Content-Type' => $mimeType ?: 'application/octet-stream',
        'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
    ]);
})->where('path', '.*')->name('storage.file');
