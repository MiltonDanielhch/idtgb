
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

    // ──────────────── PERSONAS ────────────────
    Route::prefix('people')->group(function () {
        Route::get('/', [PersonController::class, 'index'])->name('voyager.people.index');
        Route::get('/ajax/list', [PersonController::class, 'list'])->name('voyager.people.ajax.list');
        Route::post('/', [PersonController::class, 'store'])->name('voyager.people.store');
        Route::put('/{id}', [PersonController::class, 'update'])->name('voyager.people.update');
    });

    Route::resource('parentescos', ParentescoController::class)
        ->names('admin.parentescos');


    Route::resource('tasas', TasaController::class)->names('admin.tasas');

    Route::resource('exenciones', ExencionController::class)->names('admin.exenciones');


    Route::resource('inmuebles', InmuebleController::class)->names('admin.inmuebles');



    Route::resource('avaluos', AvaluoController::class)->names('admin.avaluos');

    Route::get('avaluos/{avaluo}/download', [AvaluoController::class, 'download'])->name('admin.avaluos.download');


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
