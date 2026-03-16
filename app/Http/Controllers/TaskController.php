<?php

namespace App\Http\Controllers;

use App\Models\Column;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function markViewed(Task $task)
    {
        $task->update([
            'last_viewed_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function addAttachments(Request $request, Task $task)
    {
        $request->validate([
            'files.*' => 'required|file|max:20480', // 20MB
        ]);

        $existing = $task->attachments ?? [];
        $newFiles = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store("tasks/{$task->id}/attachments", 'public');

            $newFiles[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        $task->attachments = array_merge($existing, $newFiles);
        $task->save();

        return response()->json($task->attachments);
    }


    public function paginated(Request $request, Column $column)
    {
        $tasks = $column->tasks()
            ->with('tags')
            ->withCount('comments')
            ->orderBy('position', 'desc')
            ->paginate(5);

        return response()->json($tasks);
    }

    public function reorder(Request $request, Column $column)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer'
        ]);

        foreach ($request->order as $position => $taskId) {
            Task::where('id', $taskId)
                ->where('column_id', $column->id)
                ->update(['position' => $position]);

        }

        return response()->json(['status' => 'ok']);
    }

}
