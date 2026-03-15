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


use App\Mail\TaskCreatedMail;
use App\Models\Task;
use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {

    $task = new Task([
        'title' => 'Тестовая задача через маршрут',
        'description' => 'Это описание тестовой задачи для проверки HTML-шаблона письма.',
        'priority' => 'Высокий'
    ]);

    $recipient = config('mail.from.address') ?? 'test@example.com';
    
    try {
        Mail::to($recipient)->send(new TaskCreatedMail($task));
        return "Письмо успешно отправлено на $recipient! Проверьте Mailpit или вашу почту.";
    } catch (\Exception $e) {
        return "Ошибка при отправке: " . $e->getMessage();
    }
});

require __DIR__.'/auth.php';
