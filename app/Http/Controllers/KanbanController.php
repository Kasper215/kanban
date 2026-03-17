<?php

namespace App\Http\Controllers;

use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\Column;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\TaskCreatedMail;

class KanbanController extends Controller
{
    public function getBoard($uuid)
    {
        $board = Board::where('uuid', $uuid)
            ->with([
                'columns.tasks' => function ($q) {
                    $q->orderBy('position', 'asc')
                        ->take(5);
                }
            ])
            ->firstOrFail();

        return $board;
    }

    public function duplicate(Task $task)
    {
        $newTask = Task::create([
            'board_id' => $task->board_id,
            'column_id' => $task->column_id,
            'title' => $task->title . ' (копия)',
            'description' => $task->description,
            'priority' => $task->priority,
            'due_date' => $task->due_date,
            'labels' => $task->labels,
            'position' => Task::where('column_id', $task->column_id)->count()
        ]);

        // копируем теги
        if ($task->tags()->exists()) {
            $newTask->tags()->sync($task->tags->pluck('id'));
        }



        return $newTask->load('tags');
    }


    public function createBoard(Request $request)
    {
        return Board::create([
            'uuid' => Str::uuid(),
            'title' => $request->title,
            'description' => $request->description,
            'config' => $request->config ?? []
        ]);
    }


    public function index()
    {
        return Column::with('tasks')
            ->orderBy('position')
            ->get();
    }

    public function storeColumn(Request $request, $uuid)
    {
        $board = Board::where('uuid', $uuid)->firstOrFail();

        return $board->columns()->create([
            'title' => $request->title,
            'position' => $board->columns()->count(),
            'thread' => $board->columns()->count(),
            'can_remove'=>true
        ]);
    }


    public function updateColumn(Request $request, Column $column)
    {
        $column->update($request->only('title', 'position'));
        return $column;
    }

    public function deleteColumn(Column $column)
    {
        if ($column->can_remove)
        {
            $column->delete();
            return response()->json(['status' => 'ok']);
        }
        else
            abort(400, 'Запрещено удаление, 400');
    }

    public function storeTask(Request $request, $uuid)
    {
        Log::info('storeTask called with UUID: ' . $uuid);
        Log::info('Request data: ' . json_encode($request->all()));
        
        $board = Board::where('uuid', $uuid)->firstOrFail();

        // Сдвигаем все задачи вниз
        Task::where('column_id', $request->column_id)
            ->increment('position');

        $task = $board->tasks()->create([
            'column_id' => $request->column_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'labels' => $request->labels ?? [],
            'subtasks' => $request->subtasks ?? [],
            'position' => 0//Task::where('column_id', $request->column_id)->count()
        ]);

        $task->tags()->sync($request->tag_ids ?? []);

        try {
            Log::info('Mail: Attempting to send task creation email for task: ' . $task->title);
            Mail::to('owner@example.com')->send(new TaskCreatedMail($task));
            Log::info('Mail: Task creation email sent successfully.');
        } catch (\Exception $e) {
            Log::error('Mail Error: ' . $e->getMessage());
        }

        return $task;
    }


    public function updateTask(Request $request, Task $task)
    {
        $task->update([
            'column_id' => $request->column_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'labels' => $request->labels ?? [],
            'subtasks' => $request->subtasks ?? []
        ]);
        $task->tags()->sync($request->tag_ids ?? []);
        return $task->loadCount('comments');
    }

    public function deleteTask(Task $task)
    {
        $task->delete();
        return response()->json(['status' => 'ok']);
    }

    public function moveTask(Request $request)
    {
        $task = Task::find($request->task_id);

        $task->update([
            'column_id' => $request->to_column_id,
            'position' => $request->position ?? 0
        ]);

        return $task;
    }


    public function renameColumn(Request $request, Column $column)
    {
        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $column->update([
            'title' => $request->title
        ]);

        return $column;
    }

}
