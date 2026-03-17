<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskAttachmentController extends Controller
{
    public function index(Task $task)
    {
        return $task->attachments ?? [];
    }

    public function store(Request $request, Task $task)
    {
        \Log::info('Загрузка вложений для задачи: ' . $task->id);
        
        $request->validate([
            'files.*' => 'required|file|max:20480',
        ]);

        $existing = $task->attachments ?? [];
        $newFiles = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store("tasks/{$task->id}/attachments", 'public');

                $newFiles[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $task->attachments = array_merge($existing, $newFiles);
        $task->save();

        return response()->json($task->attachments);
    }

    public function destroy(Request $request, Task $task)
    {
        $path = $request->input('path');
        if (!$path) return response()->json(['error' => 'Path is required'], 400);

        $attachments = $task->attachments ?? [];
        $task->attachments = array_values(array_filter($attachments, function ($item) use ($path) {
            return $item['path'] !== $path;
        }));
        
        $task->save();

        if (\Storage::disk('public')->exists($path)) {
            \Storage::disk('public')->delete($path);
        }

        return response()->json($task->attachments);
    }
}
