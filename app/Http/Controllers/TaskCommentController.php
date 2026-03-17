<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function index(Task $task)
    {
        return TaskComment::where('task_id', $task->id)
            ->orderBy('created_at')
            ->get();
    }

    public function store(Request $request, Task $task)
    {
        $request->validate([
            'author' => 'nullable|string',
            'text' => 'nullable|string',
            'files.*' => 'nullable|file|max:20480',
        ]);

        $attachments = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store("tasks/{$task->id}/comments", 'public');

                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'author' => $request->author,
            'text' => $request->text,
            'attachments' => $attachments,
        ]);

        return response()->json($comment);
    }

    public function destroy(TaskComment $comment)
    {
        // Удаляем все вложения комментария из хранилища
        if ($comment->attachments) {
            foreach ($comment->attachments as $file) {
                if (\Storage::disk('public')->exists($file['path'])) {
                    \Storage::disk('public')->delete($file['path']);
                }
            }
        }

        $comment->delete();

        return response()->json(['success' => true]);
    }

    public function deleteAttachment(Request $request, TaskComment $comment)
    {
        $path = $request->input('path');
        if (!$path) return response()->json(['error' => 'Path is required'], 400);

        $attachments = $comment->attachments ?? [];
        $comment->attachments = array_values(array_filter($attachments, function ($item) use ($path) {
            return $item['path'] !== $path;
        }));
        
        $comment->save();

        if (\Storage::disk('public')->exists($path)) {
            \Storage::disk('public')->delete($path);
        }

        return response()->json($comment);
    }
}
