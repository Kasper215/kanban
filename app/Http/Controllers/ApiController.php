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
            'board_uuid' => 'required|string',
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


        // Используем доску, которую уже нашел Middleware ApiAuth
        $board = $request->board;

        if (!$board) {
             Log::error('API Error: Board not found in request context.');
             return response()->json(['success' => false, 'message' => 'Board context missing'], 500);
        }

        $column = Column::where('board_id', $board->id)
            ->where('thread', $validated['thread'])
            ->first();

        if (!$column) {
            $column = Column::where('board_id', $board->id)
                ->where('thread', 0)
                ->firstOrFail();
        }

        $type = CardTypeEnum::from($validated['type']);

        $defaults = $this->defaultsByType($type);

        $payload = array_merge($defaults, $validated);

        $payload['board_id'] = $board->id;
        $payload['column_id'] = $column->id;

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
