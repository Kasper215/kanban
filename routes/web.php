<?php

use App\Http\Controllers\ManifestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Models\ApiToken;
use App\Models\Board;
use App\Services\TaskApiTester;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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
// routes/web.php

use App\Http\Controllers\BoardController;

Route::get('/board/{uuid}', [BoardController::class, 'show'])
    ->name('board.show');

Route::get('/manifest.json', [ManifestController::class, 'show']);




Route::get('/', [HomeController::class, 'index']);


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
