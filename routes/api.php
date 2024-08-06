<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\MedicionController;
use Illuminate\Support\Facades\Route;

Route::prefix('mediciones')->group(function () {
    Route::get('/', [MedicionController::class, 'index'])->name('api.mediciones_index'); // Obtiene un listado con las mediciones.
    Route::post('/nueva', [MedicionController::class, 'store'])->name('api.mediciones_store'); // Crea una nueva medición.
    Route::get('/estados', [EstadoController::class, 'index'])->name('api.medidor_index'); // Obtiene un listado con los estados de los medidores.
    Route::post('/upload', [MedicionController::class, 'upload'])->name("api.medidor_upload");
    Route::get('/login', [AuthController::class, 'login'])->name('api.login');
    Route::get('/logut', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/imagen/{nroCuenta}/{fecha}', [MedicionController::class, 'obtenerImagenPorCuentaYFecha'])->name('api.medidor_imagen');
});
