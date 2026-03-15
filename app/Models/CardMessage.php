<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardMessage extends Model
{
    protected $fillable = [
        'task_id',
        'sender_type',
        'sender_label',
        'payload',
        'message',
        'attachments',
        'is_read',
    ];

    protected $casts = [
        'payload' => 'array',
        'attachments' => 'array',
        'is_read' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
