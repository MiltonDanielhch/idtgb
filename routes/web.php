
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
use App\Http\Controllers\UfvController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirección raíz y login
Route::redirect('login', 'admin/login')->name('login');
Route::redirect('/', 'admin');

// Ruta pública (sin login)
Route::get('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'formulario'])->name('calculadora.beni.form');
Route::post('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'calcular'])->name('calculadora.beni.calcular');

// url link
// Route::get('/verificar/{hash}', [CalculadoraBeniController::class, 'show'])
//      ->name('qr.verificar');

// Grupo principal con middleware personalizado
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {

    // Rutas de Voyager (no tocar)
    Voyager::routes();

 Route::prefix('people')->group(function () {
        Route::get('/', [PersonController::class, 'index'])->name('admin.people.index');
        Route::get('/ajax/list', [PersonController::class, 'list'])->name('admin.people.ajax.list');
        Route::get('/create', [PersonController::class, 'create'])->name('admin.people.create');
        Route::post('/', [PersonController::class, 'store'])->name('admin.people.store');
        Route::get('/{person}/edit', [PersonController::class, 'edit'])->name('admin.people.edit');
        Route::put('/{person}', [PersonController::class, 'update'])->name('admin.people.update');
        Route::delete('/{person}', [PersonController::class, 'destroy'])->name('admin.people.destroy');
        Route::get('/{person}', [PersonController::class, 'show'])->name('admin.people.show');
    });

    // ──────────────── PARENTESCO ────────────────
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

    // Route::resource('tasas', TasaController::class)->names('admin.tasas');
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


    // Route::resource('inmuebles', InmuebleController::class)->names('admin.inmuebles');

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


    // Route::resource('avaluos', AvaluoController::class)->names('admin.avaluos');

    // Route::get('avaluos/{avaluo}/download', [AvaluoController::class, 'download'])->name('admin.avaluos.download');

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

    Route::resource('tramites', TramiteController::class)->names('admin.tramites');

    Route::get('tramites/{tramite}/a01', [TramiteController::class, 'a01'])->name('admin.tramites.a01');

    Route::prefix('tramites/{tramite}')->group(function () {
        Route::get('pagar', [PagoController::class, 'create'])->name('admin.tramites.pagar');
        Route::post('pagar', [PagoController::class, 'store'])->name('admin.tramites.pago.store');
        Route::get('pago/{pago}/comprobante', [PagoController::class, 'comprobante'])->name('admin.pago.comprobante');
    });

    Route::prefix('tramites/{tramite}')->group(function () {
        Route::get('exenciones', [TramiteExencionController::class, 'index'])->name('admin.tramites.exenciones.index');
        Route::get('exenciones/create', [TramiteExencionController::class, 'create'])->name('admin.tramites.exenciones.create');
        Route::post('exenciones', [TramiteExencionController::class, 'store'])->name('admin.tramites.exenciones.store');
        Route::delete('exenciones/{exencion}', [TramiteExencionController::class, 'destroy'])->name('admin.tramites.exenciones.destroy');
    });

    Route::prefix('tramites/{tramite}')->group(function () {
        Route::get('adquirentes', [AdquirenteTramiteController::class, 'index'])->name('admin.tramites.adquirentes.index');
        Route::get('adquirentes/create', [AdquirenteTramiteController::class, 'create'])->name('admin.tramites.adquirentes.create');
        Route::post('adquirentes', [AdquirenteTramiteController::class, 'store'])->name('admin.tramites.adquirentes.store');
        Route::delete('adquirentes/{adquirente}', [AdquirenteTramiteController::class, 'destroy'])->name('admin.tramites.adquirentes.destroy');
    });


    Route::prefix('tramites/{tramite}')->group(function () {
        Route::get('disponentes', [DisponenteTramiteController::class, 'index'])->name('admin.tramites.disponentes.index');
        Route::get('disponentes/create', [DisponenteTramiteController::class, 'create'])->name('admin.tramites.disponentes.create');
        Route::post('disponentes', [DisponenteTramiteController::class, 'store'])->name('admin.tramites.disponentes.store');
        Route::delete('disponentes/{disponente}', [DisponenteTramiteController::class, 'destroy'])->name('admin.tramites.disponentes.destroy');
    });

    Route::prefix('tramites/{tramite}')->group(function () {
        Route::get('documentos', [DocumentoController::class, 'index'])->name('admin.tramites.documentos.index');
        Route::get('documentos/create', [DocumentoController::class, 'create'])->name('admin.tramites.documentos.create');
        Route::post('documentos', [DocumentoController::class, 'store'])->name('admin.tramites.documentos.store');
        Route::delete('documentos/{documento}', [DocumentoController::class, 'destroy'])->name('admin.tramites.documentos.destroy');
        Route::get('documentos/{documento}/download', [DocumentoController::class, 'download'])->name('admin.tramites.documentos.download');
    });

    Route::resource('ufvs', UfvController::class)->names('admin.ufvs');
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
