<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CardMessage;
use App\Models\Task;
use App\Models\Board;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    // Получить все сообщения по карточке
    public function index($taskId)
    {
        return response()->json(
            CardMessage::where('task_id', $taskId)
                ->orderBy('created_at')
                ->get()
        );
    }

    // Отправить сообщение (менеджер → внешний пользователь)
    public function store(Request $request, $taskId)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $task = Task::query()
            ->where("id", $taskId)
            ->firstOrFail();

        $message = CardMessage::create([
            'task_id' => $taskId,
            'sender_type' => 'manager',
            'sender_label' => auth()->user()->name ?? 'Менеджер',
            'message' => $validated['message'],
        ]);

        $board = $request->board;

        // Отправка на webhook, если указан
        if (!empty($board->config['webhook_url'])) {
            Http::post($board->config['webhook_url'], [
                'task_id' => $taskId,
                'message' => $message->message,
                'sender' => $message->sender_label,
                'payload' => $task->data ?? []
            ]);
        }

        return response()->json($message);
    }

    // Пометить сообщение как прочитанное
    public function markRead($messageId)
    {
        $message = CardMessage::query()
            ->where("id", $messageId)
            ->firstOrFail();

        $message->update(['is_read' => true]);
        return response()->json($message);
    }
}
