
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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirección raíz y login
Route::redirect('login', 'admin/login')->name('login');
// Route::redirect('/', 'admin');
Route::get('/', function () {
    return view('home');
});

// Ruta pública (sin login)
Route::get('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'formulario'])->name('calculadora.beni.form');
Route::post('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'calcular'])->name('calculadora.beni.calcular');
Route::post('/calculadora-idtgb-beni-pdf', [CalculadoraBeniController::class, 'descargarPdf']);

// url link
// Route::get('/verificar/{hash}', [CalculadoraBeniController::class, 'show'])
//      ->name('qr.verificar');

Route::get('/validar/{hash}', [ValidacionController::class, 'show'])->name('tramite.validar');

// Grupo principal con middleware personalizado
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {

    // Rutas de Voyager
    Voyager::routes();

    // ──────────────── REPORTES ────────────────
    Route::prefix('reportes')->name('admin.reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
    });

    Route::prefix('people')->group(function () {        Route::get('/', [PersonController::class, 'index'])->name('admin.people.index');
        Route::get('/ajax/list', [PersonController::class, 'list'])->name('admin.people.ajax.list');
        Route::get('/create', [PersonController::class, 'create'])->name('admin.people.create');
        Route::post('/', [PersonController::class, 'store'])->name('admin.people.store');
        Route::get('/{person}/edit', [PersonController::class, 'edit'])->name('admin.people.edit');
        Route::put('/{person}', [PersonController::class, 'update'])->name('admin.people.update');
        Route::delete('/{person}', [PersonController::class, 'destroy'])->name('admin.people.destroy');
        Route::get('/{person}', [PersonController::class, 'show'])->name('admin.people.show');
    });

    Route::prefix('parentescos')->group(function () {
        Route::get('/', [ParentescoController::class, 'index'])->name('admin.parentescos.index');
        Route::get('/create', [ParentescoController::class, 'create'])->name('admin.parentescos.create');
        Route::get('/ajax/list', [ParentescoController::class, 'list'])->name('admin.parentescos.ajax.list');
        Route::get('/{parentesco}', [ParentescoController::class, 'show'])->name('admin.parentescos.show');
        Route::post('/', [ParentescoController::class, 'store'])->name('admin.parentescos.store');
        Route::get('/{parentesco}/edit', [ParentescoController::class, 'edit'])->name('admin.parentescos.edit');
        Route::put('/{parentesco}', [ParentescoController::class, 'update'])->name('admin.parentescos.update');
        Route::delete('/{parentesco}', [ParentescoController::class, 'destroy'])->name('admin.parentescos.destroy');
    });

    Route::prefix('exenciones')->group(function () {
        Route::get('/', [ExencionController::class, 'index'])->name('admin.exenciones.index');
        Route::get('/create', [ExencionController::class, 'create'])->name('admin.exenciones.create');
        Route::get('/ajax/list', [ExencionController::class, 'list'])->name('admin.exenciones.ajax.list');
        Route::get('/{exencion}', [ExencionController::class, 'show'])->name('admin.exenciones.show');
        Route::post('/', [ExencionController::class, 'store'])->name('admin.exenciones.store');
        Route::get('/{exencion}/edit', [ExencionController::class, 'edit'])->name('admin.exenciones.edit');
        Route::put('/{exencion}', [ExencionController::class, 'update'])->name('admin.exenciones.update');
        Route::delete('/{exencion}', [ExencionController::class, 'destroy'])->name('admin.exenciones.destroy');
    });
    Route::prefix('tasas')->group(function () {
        Route::get('/', [TasaController::class, 'index'])->name('admin.tasas.index');
        Route::get('/create', [TasaController::class, 'create'])->name('admin.tasas.create');
        Route::get('/ajax/list', [TasaController::class, 'list'])->name('admin.tasas.ajax.list');
        Route::get('/{tasa}', [TasaController::class, 'show'])->name('admin.tasas.show');
        Route::post('/', [TasaController::class, 'store'])->name('admin.tasas.store');
        Route::get('/{tasa}/edit', [TasaController::class, 'edit'])->name('admin.tasas.edit');
        Route::put('/{tasa}', [TasaController::class, 'update'])->name('admin.tasas.update');
        Route::delete('/{tasa}', [TasaController::class, 'destroy'])->name('admin.tasas.destroy');
    });

    Route::resource('exenciones', ExencionController::class)->names('admin.exenciones');

    Route::prefix('inmuebles')->group(function () {
        Route::get('/', [InmuebleController::class, 'index'])->name('admin.inmuebles.index');
        Route::get('/create', [InmuebleController::class, 'create'])->name('admin.inmuebles.create');
        Route::get('/ajax/list', [InmuebleController::class, 'list'])->name('admin.inmuebles.ajax.list');
        Route::get('/{inmueble}', [InmuebleController::class, 'show'])->name('admin.inmuebles.show');
        Route::post('/', [InmuebleController::class, 'store'])->name('admin.inmuebles.store');
        Route::get('/{inmueble}/edit', [InmuebleController::class, 'edit'])->name('admin.inmuebles.edit');
        Route::put('/{inmueble}', [InmuebleController::class, 'update'])->name('admin.inmuebles.update');
        Route::delete('/{inmueble}', [InmuebleController::class, 'destroy'])->name('admin.inmuebles.destroy');
    });

    Route::prefix('avaluos')->group(function () {
        Route::get('/', [AvaluoController::class, 'index'])->name('admin.avaluos.index');
        Route::get('/create', [AvaluoController::class, 'create'])->name('admin.avaluos.create');
        Route::get('/ajax/list', [AvaluoController::class, 'list'])->name('admin.avaluos.ajax.list');
        Route::get('/{avaluo}', [AvaluoController::class, 'show'])->name('admin.avaluos.show');
        Route::post('/', [AvaluoController::class, 'store'])->name('admin.avaluos.store');
        Route::get('/{avaluo}/edit', [AvaluoController::class, 'edit'])->name('admin.avaluos.edit');
        Route::put('/{avaluo}', [AvaluoController::class, 'update'])->name('admin.avaluos.update');
        Route::delete('/{avaluo}', [AvaluoController::class, 'destroy'])->name('admin.avaluos.destroy');
        Route::get('/{avaluo}/download', [AvaluoController::class, 'download'])->name('admin.avaluos.download');
    });

    Route::prefix('tramites')->group(function () {
        Route::get('/', [TramiteController::class, 'index'])->name('admin.tramites.index');
        Route::get('/create', [TramiteController::class, 'create'])->name('admin.tramites.create');
        Route::get('/ajax/list', [TramiteController::class, 'list'])->name('admin.tramites.ajax.list');
        Route::get('/{tramite}', [TramiteController::class, 'show'])->name('admin.tramites.show');
        Route::post('/', [TramiteController::class, 'store'])->name('admin.tramites.store');
        Route::get('/{tramite}/edit', [TramiteController::class, 'edit'])->name('admin.tramites.edit');
        Route::put('/{tramite}', [TramiteController::class, 'update'])->name('admin.tramites.update');
        Route::delete('/{tramite}', [TramiteController::class, 'destroy'])->name('admin.tramites.destroy');
    });
    Route::get('tramites/{tramite}/a01', [TramiteController::class, 'a01'])->name('admin.tramites.a01');

    /*  Trámites - Inmuebles (pivote)  */
    Route::prefix('tramites/{tramite}/inmuebles')->name('admin.tramites.inmuebles.')->group(function () {
        Route::get('/', [TramiteInmuebleController::class, 'index'])->name('index');
        Route::get('/list', [TramiteInmuebleController::class, 'list'])->name('ajax.list');
        Route::get('/create', [TramiteInmuebleController::class, 'create'])->name('create');
        Route::post('/', [TramiteInmuebleController::class, 'store'])->name('store');
        Route::delete('/{item}', [TramiteInmuebleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/exenciones')->name('admin.tramites.exenciones.')->group(function () {
        Route::get('/', [TramiteExencionController::class, 'index'])->name('index');
        Route::get('/list', [TramiteExencionController::class, 'list'])->name('ajax.list');
        Route::get('/create', [TramiteExencionController::class, 'create'])->name('create');
        Route::post('/', [TramiteExencionController::class, 'store'])->name('store');
        Route::get('/{item}', [TramiteExencionController::class, 'show'])->name('show');
        Route::delete('/{item}', [TramiteExencionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/adquirentes')->name('admin.tramites.adquirentes.')->group(function () {
        Route::get('/', [AdquirenteTramiteController::class, 'index'])->name('index');
        Route::get('/list', [AdquirenteTramiteController::class, 'list'])->name('ajax.list');
        Route::get('/create', [AdquirenteTramiteController::class, 'create'])->name('create');
        Route::post('/', [AdquirenteTramiteController::class, 'store'])->name('store');
        Route::get('/{item}', [AdquirenteTramiteController::class, 'show'])->name('show');
        Route::delete('/{item}', [AdquirenteTramiteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/disponentes')->name('admin.tramites.disponentes.')->group(function () {
        Route::get('/', [DisponenteTramiteController::class, 'index'])->name('index');
        Route::get('/list', [DisponenteTramiteController::class, 'list'])->name('ajax.list');
        Route::get('/create', [DisponenteTramiteController::class, 'create'])->name('create');
        Route::post('/', [DisponenteTramiteController::class, 'store'])->name('store');
        Route::get('/{item}', [DisponenteTramiteController::class, 'show'])->name('show');
        Route::delete('/{item}', [DisponenteTramiteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/documentos')->name('admin.tramites.documentos.')->group(function () {
        Route::get('/', [DocumentoController::class, 'index'])->name('index');
        Route::get('/list', [DocumentoController::class, 'list'])->name('ajax.list');
        Route::get('/create', [DocumentoController::class, 'create'])->name('create');
        Route::post('/', [DocumentoController::class, 'store'])->name('store');
        Route::get('/{item}', [DocumentoController::class, 'show'])->name('show');
        Route::delete('/{item}', [DocumentoController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tramites/{tramite}/pagos')->name('admin.tramites.pagos.')->group(function () {
        Route::get('/', [PagoController::class, 'index'])->name('index');
        Route::get('/list', [PagoController::class, 'list'])->name('ajax.list');
        Route::get('/create', [PagoController::class, 'create'])->name('create');
        Route::post('/', [PagoController::class, 'store'])->name('store');
        Route::get('/{pago}', [PagoController::class, 'show'])->name('show');
        Route::delete('/{pago}', [PagoController::class, 'destroy'])->name('destroy');
    });

    // Route::resource('ufvs', UfvController::class)->names('admin.ufvs');

    Route::prefix('ufvs')->name('admin.ufvs.')->group(function () {
        Route::get('/', [UfvController::class, 'index'])->name('index');
        Route::get('/list', [UfvController::class, 'list'])->name('ajax.list');
        Route::get('/create', [UfvController::class, 'create'])->name('create');
        Route::post('/', [UfvController::class, 'store'])->name('store');
        Route::post('/import', [UfvController::class, 'import'])->name('import');
        Route::get('/{ufv}', [UfvController::class, 'show'])->name('show');
    });

    // ──────────────── USUARIOS ────────────────
    Route::prefix('users')->group(function () {
        Route::get('/ajax/list', [UserController::class, 'list'])->name('voyager.users.ajax.list');
        Route::post('/store', [UserController::class, 'store'])->name('voyager.users.store');
        Route::put('/{id}', [UserController::class, 'update'])->name('voyager.users.update');
        Route::delete('/{id}/deleted', [UserController::class, 'destroy'])->name('voyager.users.destroy');
    });

    // ──────────────── ROLES ────────────────
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
            'message' => 'Cache eliminada.',
            'alert-type' => 'success'
        ]);
    })->name('clear.cache');

});
