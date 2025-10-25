<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
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

// -------------------------------------------------------------------------
// RUTAS PÚBLICAS (Sin autenticación)
// -------------------------------------------------------------------------
Route::get('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'formulario'])->name('calculadora.beni.form');
Route::post('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'calcular'])->name('calculadora.beni.calcular');
Route::post('/calculadora-idtgb-beni-pdf', [CalculadoraBeniController::class, 'descargarPdf']);

Route::get('/validar/{hash}', [ValidacionController::class, 'show'])->name('tramite.validar');

// -------------------------------------------------------------------------
// GRUPO PRINCIPAL DE ADMINISTRACIÓN (Con autenticación y middleware)
// -------------------------------------------------------------------------
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {

    // Rutas de Voyager (Panel de administración)
    Voyager::routes();

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

    Route::resource('exenciones', ExencionController::class)->names('admin.exenciones')->parameters(['exenciones' => 'exencion']);
    Route::get('exenciones/ajax/list', [ExencionController::class, 'list'])->name('admin.exenciones.ajax.list');

    Route::resource('tasas', TasaController::class)->names('admin.tasas');
    Route::get('tasas/ajax/list', [TasaController::class, 'list'])->name('admin.tasas.ajax.list');

    Route::resource('inmuebles', InmuebleController::class)->names('admin.inmuebles');
    Route::get('inmuebles/ajax/list', [InmuebleController::class, 'list'])->name('admin.inmuebles.ajax.list');

    Route::get('inmuebles/ajax/search', [InmuebleController::class, 'ajaxSearch'])->name('admin.inmuebles.ajax.search');

    Route::resource('avaluos', AvaluoController::class)->names('admin.avaluos');
    Route::get('avaluos/ajax/list', [AvaluoController::class, 'list'])->name('admin.avaluos.ajax.list');
    Route::get('avaluos/{avaluo}/download', [AvaluoController::class, 'download'])->name('admin.avaluos.download');

    // Asistente para creación de trámites
    Route::prefix('tramites/wizard')->name('admin.tramites.wizard.')->group(function () {
        Route::get('create-step-1', [TramiteWizardController::class, 'createStep1'])->name('create.step1');
        Route::post('post-step-1', [TramiteWizardController::class, 'postStep1'])->name('post.step1');
        Route::get('create-step-2', [TramiteWizardController::class, 'createStep2'])->name('create.step2');
        Route::post('post-step-2', [TramiteWizardController::class, 'postStep2'])->name('post.step2');
        Route::post('add-disponente', [TramiteWizardController::class, 'addDisponente'])->name('add.disponente');
        Route::delete('remove-disponente/{person_id}', [TramiteWizardController::class, 'removeDisponente'])->name('remove.disponente');

        // Step 3: Adquirentes
        Route::get('create-step-3', [TramiteWizardController::class, 'createStep3'])->name('create.step3');
        Route::post('post-step-3', [TramiteWizardController::class, 'postStep3'])->name('post.step3');
        Route::post('add-adquirente', [TramiteWizardController::class, 'addAdquirente'])->name('add.adquirente');
        Route::delete('remove-adquirente/{person_id}', [TramiteWizardController::class, 'removeAdquirente'])->name('remove.adquirente');

        // Step 4: Inmueble
        Route::get('create-step-4', [TramiteWizardController::class, 'createStep4'])->name('create.step4');
        Route::post('post-step-4', [TramiteWizardController::class, 'postStep4'])->name('post.step4');
        Route::post('add-inmueble', [TramiteWizardController::class, 'addInmueble'])->name('add.inmueble');
        Route::delete('remove-inmueble', [TramiteWizardController::class, 'removeInmueble'])->name('remove.inmueble');

        // Step 5: Documentos
        Route::get('create-step-5', [TramiteWizardController::class, 'createStep5'])->name('create.step5');
        Route::post('post-step-5', [TramiteWizardController::class, 'postStep5'])->name('post.step5');
        Route::post('add-documento', [TramiteWizardController::class, 'addDocumento'])->name('add.documento');
        Route::get('remove-documento/{doc_id}', [TramiteWizardController::class, 'removeDocumento'])->name('remove.documento');

        // Step 6: Exenciones
        Route::get('create-step-6', [TramiteWizardController::class, 'createStep6'])->name('create.step6');
        Route::post('post-step-6', [TramiteWizardController::class, 'postStep6'])->name('post.step6');
        Route::post('add-exencion', [TramiteWizardController::class, 'addExencion'])->name('add.exencion');
        Route::get('remove-exencion/{exencion_id}', [TramiteWizardController::class, 'removeExencion'])->name('remove.exencion');

        // Step 7: Resumen y Guardar
        Route::get('create-step-7', [TramiteWizardController::class, 'createStep7'])->name('create.step7');
        Route::post('store', [TramiteWizardController::class, 'store'])->name('store');

        // Cancelar
        Route::get('cancel', [TramiteWizardController::class, 'cancelWizard'])->name('cancel');

        // Rutas AJAX para el asistente
        Route::get('ajax/person-list', [TramiteWizardController::class, 'ajaxPersonList'])->name('ajax.personList');
    });



    // ──────────────── TRÁMITES ────────────────
    // Ruta personalizada que debe ir ANTES que el resource para no ser capturada por el método show del resource.
    Route::get('tramites/{tramite}/a01', [TramiteController::class, 'a01'])->name('admin.tramites.a01');

    // El resource se mantiene para las rutas show, edit, update, destroy.
    // Los métodos create y store se excluyen porque ahora los maneja el TramiteWizardController.
    Route::resource('tramites', TramiteController::class)->names('admin.tramites')->except(['create', 'store']);
    // Redirigimos la ruta de creación al primer paso del asistente.
    Route::get('tramites/create', fn() => redirect()->route('admin.tramites.wizard.create.step1'))->name('admin.tramites.create');
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
    Route::get('/clear-cache', function () {
        Artisan::call('optimize:clear');
        return redirect('/admin/profile')->with([
            'message'    => 'Cache eliminada.',
            'alert-type' => 'success',
        ]);
    })->name('clear.cache');
});
