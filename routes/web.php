<?php

use App\Http\Controllers\Web\ReservationController;
use App\Http\Resources\V1\ReservationCollection;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/', static function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/home', static function () {
    return Inertia::render('Home', [
        'reservations' => new ReservationCollection(Auth::user()?->reservations()->get()),
        'minReservation' => (new CarbonImmutable())->format('Y-m-d'),
        'maxReservation' => (new CarbonImmutable())->modify('+3 months')->format('Y-m-d'),
    ]);
})->middleware(['auth', 'verified'])->name('home');

Route::post('/reservation', [ReservationController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('make-reservation');

Route::delete('/reservation/{id}', [ReservationController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('cancel-reservation');

require __DIR__.'/auth.php';
