<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Models\ApiToken;
use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('boards')->group(function () {
    // Получить доску по UUID

    Route::get('/choose-template', [HomeController::class, 'chooseTemplate']);

    Route::get('{uuid}', [KanbanController::class, 'getBoard']);
    // Обновить доску
    Route::put('{uuid}', [BoardController::class, 'update'])
        ->middleware('dispatch.board');
    // Создать доску
    Route::post('/', [KanbanController::class, 'createBoard']);
    // Экспорт доски
    Route::get('{board}/export', [BoardController::class, 'export']);
    // Теги доски
    Route::get('{uuid}/tags', [TagController::class, 'index']);
    Route::post('{uuid}/tags', [TagController::class, 'store']);
    // Колонки доски
    Route::post('{uuid}/columns', [KanbanController::class, 'storeColumn']);
    // Задачи доски
    Route::post('{uuid}/tasks', [KanbanController::class, 'storeTask']);
    // Переупорядочивание колонок
    Route::put('{uuid}/columns/reorder', [ColumnController::class, 'reorder'])
        ->middleware('dispatch.board');
    Route::post('/{uuid}/config', [BoardController::class, 'setConfig']);
    Route::post('/{uuid}/apply-template', [HomeController::class, 'applyTemplate']);

});


Route::prefix('cards')->group(function () {
    // История сообщений по карточке
    Route::get('/{cardId}/messages', [ChatController::class, 'index']);

    // Отправка нового сообщения (с указанием board для webhook)
    Route::post('/{cardId}/send', [ChatController::class, 'store']);

    // Пометить сообщение как прочитанное
    Route::post('/mark-as-read/{messageId}', [ChatController::class, 'markRead']);
});


Route::prefix('columns')->group(function () {
    // Обновить колонку
    Route::put('{column}', [KanbanController::class, 'updateColumn'])
        ->middleware('dispatch.board');
    // Удалить колонку
    Route::delete('{column}', [KanbanController::class, 'deleteColumn'])
        ->middleware('dispatch.board');
    // Переименовать колонку
    Route::put('{column}', [KanbanController::class, 'renameColumn'])
        ->middleware('dispatch.board');
    // Получить задачи колонки (пагинация)
    Route::get('{column}/tasks', [TaskController::class, 'paginated'])
        ->middleware('dispatch.board');
    // Переупорядочивание задач в колонке
    Route::put('{column}/tasks/reorder', [TaskController::class, 'reorder'])
        ->middleware('dispatch.board');
});

Route::prefix('tasks')->group(function () {
    // Обновить задачу
    Route::put('{task}', [KanbanController::class, 'updateTask'])
        ->middleware('dispatch.board');
    // Удалить задачу
    Route::delete('{task}', [KanbanController::class, 'deleteTask'])
        ->middleware('dispatch.board');
    // Перемещение задачи
    Route::post('move', [KanbanController::class, 'moveTask'])
        ->middleware('dispatch.board');
    // Дублирование задачи
    Route::post('{task}/duplicate', [KanbanController::class, 'duplicate'])
        ->middleware('dispatch.board');
    // Отметить задачу как просмотренную
    Route::post('{task}/view', [TaskController::class, 'markViewed']);
});

Route::prefix('tags')->group(function () {
    Route::delete('{tag}', [TagController::class, 'destroy']);
});

Route::prefix('push')->group(function () {
    Route::post('subscribe', [PushController::class, 'subscribe']);
    Route::get('test', [PushController::class, 'sendTest']);
});

Route::post("/token", function (Request $request){
    $request->validate([
        "uuid"=>"required"
    ]);

    $uuid = $request->uuid;

    $board = Board::where('uuid', $uuid)->firstOrFail();

    $token = 'kb_' . Str::random(40);

    $tokens = ApiToken::query()
        ->where( 'board_id', $board->id)
        ->orderBy("created_at", "asc")
        ->get();

/*    $limit = env("APP_TOKENS_LIMIT", 10);

    if (count($tokens)>=$limit)
    {
        $tokens[0]->delete();
    }*/

    ApiToken::create([
        'board_id' => $board->id,
        'token' => hash('sha256', $token),
        'abilities' => json_encode([
            'tasks.read',
            'tasks.write',
            'comments.write'
        ])
    ]);

    return [
        "token"=>$token
    ];
});

// --- Комментарии и вложения (внутренний фронт, без токена) ---
Route::prefix('task')->group(function () {
    Route::get('{task}/comments', [TaskCommentController::class, 'index']);
    Route::post('{task}/comment', [TaskCommentController::class, 'store']);
    Route::get('{task}/attachments', [TaskAttachmentController::class, 'index']);
    Route::post('{task}/attachments', [TaskAttachmentController::class, 'store']);
});

// --- Внешний API (требует токен) ---
Route::prefix('task')
    ->middleware(['api.auth'])
    ->group(function () {
    Route::post('create', [ApiController::class, 'handler']);
});

Route::prefix('test')->group(function () {
    Route::post('/webhook', [BoardController::class,"testWebhook"]);
    Route::post('/email', [BoardController::class,"testEmail"]);
});
