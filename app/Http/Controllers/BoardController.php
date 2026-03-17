<?php

namespace App\Http\Controllers;

use App\Exports\BoardExport;
use App\Models\Board;
use Illuminate\Http\Request;
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

        if (!is_null($board))
            return Inertia::render('Board/Show', [
                'board' => $board,
                'vapidPublicKey' => config('webpush.vapid.public_key')
            ]);

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

        // Сохраняем UUID в сессию
        $request->session()->put('board_uuid', $board->uuid);

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
