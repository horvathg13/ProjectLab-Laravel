<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskPriorities extends Model
{
    use HasFactory;

    protected $table = 'task_priorities';

    protected $fillable = [
        'task_priority'
    ];

    public function tasksPriority(){
        return $this-> belongsTo(Tasks::class);
    }
}
