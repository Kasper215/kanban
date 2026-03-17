<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'column_id',
        'title',
        'description',
        'priority',
        'due_date',
        'last_viewed_at',
        'labels',
        'attachments',
        'subtasks',
        'type',
        'data',
        'position'
    ];

    protected $casts = [
        'labels' => 'array',
        'data' => 'array',
        'subtasks' => 'array',
        'attachments' => 'array',
        'type' => 'integer',
        'due_date' => 'date',
        'last_viewed_at' => 'datetime'
    ];

    protected $with=["tags"];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function column()
    {
        return $this->belongsTo(Column::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class,"task_tag","task_id");
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

}

