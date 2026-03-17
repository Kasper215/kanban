<?php

namespace App\Http\Controllers;

use App\Exports\BoardExport;
use App\Mail\TaskCreatedMail;
use App\Models\Board;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BoardController extends Controller
{
    public function show(Request $request, $uuid)
    {
        $board = Board::where('uuid', $uuid)
            ->with([
                'columns.tasks' => function ($q) {
                    $q->withCount('comments')
                        ->orderBy('position', 'asc')
                        ->take(5);
                }
            ])
            ->first();


        if (!is_null($board)) {
            Inertia::share(["board_uuid" => $board->uuid]);
            return Inertia::render('Board/Show', [
                'board' => $board,
                'vapidPublicKey' => config('webpush.vapid.public_key')
            ]);
        }


        // Создаём новую доску
        $board = Board::create([
            'uuid' => Str::uuid(),
            'title' => 'Моя доска',
            'description' => 'Личная канбан‑доска'
        ]);

        // Список колонок по умолчанию
        $defaultColumns = [
            'Отзывы',
            'Начисления баллов',
            'Вопросы',
            'Конкурсы',
            'Заказы',
            'Вывод средств',
            'Доставка',
            'Ответы',
            'Обратная связь'
        ];

        $board->columns()->create([
            'title' => 'По умолчанию',
            'position' => 0,
            'can_remove' => false,
            'thread' => 0,
        ]);

        foreach ($defaultColumns as $index => $title) {
            $board->columns()->create([
                'title' => $title,
                'position' => $index + 1,
                'thread' => $index + 1
            ]);
        }


        return redirect('/board/' . $board->uuid);
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $board = Board::where('uuid', $uuid)
            ->firstOrFail();

        $board->update([
            'title' => $request->title
        ]);


        return response()->json($board);
    }

    public function testWebhook(Request $request)
    {

        $request->validate([
            "url" => "required"
        ]);

        $task = new Task([
            'title' => 'Тестовая задача через маршрут',
            'description' => 'Это описание тестовой задачи для проверки вебхука.',
            'priority' => 'medium'
        ]);

        $url = $request->url;

        $data = Http::post($url, [
            'task_id' => $task->id,
            'message' => "Сообщение",
            'sender' => "Отправитель",
            'payload' => $task->data ?? []
        ]);

        return response()->json([
            'status' => 'ok',
            'type' => 'webhook_test',
            'received' => $data,
            'message' => 'Webhook успешно получен (заглушка)'
        ]);
    }

    public function testEmail(Request $request)
    {

        $request->validate([
            "email" => "required"
        ]);

        $task = new Task([
            'title' => 'Тестовая задача через маршрут',
            'description' => 'Это описание тестовой задачи для проверки HTML-шаблона письма.',
            'priority' => 'medium'
        ]);

        $recipient = $request->email ?? null;

        try {
            Mail::to($recipient)->send(new TaskCreatedMail($task));
            $msg = "Письмо успешно отправлено на $recipient! Проверьте Mailpit или вашу почту.";
        } catch (\Exception $e) {
            $msg = "Ошибка при отправке: " . $e->getMessage();
        }

        return response()->json([
            'status' => 'ok',
            'type' => 'email_test',
            'received' => $request->all(),
            'message' => $msg
        ]);
    }

    public function setConfig(Request $request, $uuid)
    {
        $validated = $request->validate([
            'webhook_url' => 'nullable|url',
            'email_for_notification' => 'nullable|email',
            'need_email_notification' => 'boolean',
        ]);

        $board = Board::where('uuid', $uuid)
            ->firstOrFail();

        $config = $board->config ?? [];
        $config["webhook_url"] = $request->get("webhook_url") ?? null;
        $config["email_for_notification"] = $request->get("email_for_notification") ?? null;
        $config["need_email_notification"] = $request->get("need_email_notification") ?? false;

        $board->config = $config;
        $board->save();

        return response()->json($board->config);
    }

    public function export(Board $board)
    {
        return Excel::download(new BoardExport($board), "board_{$board->id}.xlsx");
    }
}
