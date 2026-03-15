<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Column;
use App\Models\Board;
use Illuminate\Http\Request;
use App\Enums\CardTypeEnum;
use Illuminate\Support\Facades\Mail;
use App\Mail\TaskCreatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    public function handler(Request $request)
    {
        $validated = $request->validate([
            'thread' => 'required|integer',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'type' => 'required|integer|min:0|max:4',
            'due_date' => 'nullable|date',
            'labels' => 'nullable|array',
            'data' => 'nullable|array',
            'subtasks' => 'nullable|array',
        ]);

        // 1. Находим доску по UUID
        $board = $request->board;

        // 2. Находим колонку по thread
        $column = Column::where('board_id', $board->id)
            ->where('thread', $validated['thread'])
            ->first();

        // 3. Если колонки нет → используем thread = 0
        if (!$column) {
            $column = Column::where('board_id', $board->id)
                ->where('thread', 0)
                ->firstOrFail();
        }

        $type = CardTypeEnum::from($validated['type']);

        // 4. Дефолтные данные по типу
        $defaults = $this->defaultsByType($type);

        // 5. Объединяем дефолты и входные данные
        $payload = array_merge($defaults, $validated);

        // 6. Проставляем реальные ID
        $payload['board_id'] = $board->id;
        $payload['column_id'] = $column->id;

        // 7. Создаём задачу
        $task = Task::create($payload);

        $mailTo = $board->config["email_for_notification"] ?? null;
        $canSendEmailNotification = $board->config["need_email_notification"] ?? false;

        if (!is_null($mailTo) && $canSendEmailNotification) {
            try {
                Mail::to($mailTo)->send(new TaskCreatedMail($task));
            } catch (\Exception $e) {
                Log::warning('Could not send API task creation email: ' . $e->getMessage());
            }
        }


        return response()->json([
            'success' => true,
            'task' => $task
        ]);
    }

    private function defaultsByType(CardTypeEnum $type): array
    {
        return match ($type) {

            CardTypeEnum::BASE => [
                'priority' => 'low',
                'labels' => [],
                'data' => [],
                'subtasks' => [],
            ],

            CardTypeEnum::USER => [
                'priority' => 'medium',
                'labels' => ['client'],
                'data' => [
                    'phone' => null,
                    'email' => null,
                    'city' => null,
                    'company' => null,
                    'position' => null,
                    'notes' => null,
                ],
                'subtasks' => [],
            ],

            CardTypeEnum::ORDER => [
                'priority' => 'high',
                'labels' => ['order'],
                'data' => [
                    'product' => null,
                    'quantity' => null,
                    'price' => null,
                    'discount' => null,
                    'address' => null,
                    'comment' => null,
                    'parts' => [],
                ],
                'subtasks' => [],
            ],

            CardTypeEnum::TEXT => [
                'priority' => 'low',
                'labels' => ['text'],
                'data' => [
                    'question' => null,
                    'answer' => null,
                ],
                'subtasks' => [],
            ],

            CardTypeEnum::FINANCE => [
                'priority' => 'medium',
                'labels' => ['finance'],
                'data' => [
                    'amount' => null,
                    'currency' => '₽',
                    'operation' => null,
                    'balanceAfter' => null,
                    'comment' => null,
                ],
                'subtasks' => [],
            ],
        };
    }
}
