<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskAttachmentController extends Controller
{
    public function index(Task $task)
    {
        return $task->attachments ?? [];
    }

    public function store(Request $request, Task $task)
    {
        Log::info('Загрузка вложений для задачи: ' . $task->id);
        if (!$request->hasFile('files')) {
            Log::warning('Файлы не найдены в запросе');
        }

        $request->validate([
            'files.*' => 'required|file|max:20480',
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
}
