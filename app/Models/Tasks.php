<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tasks extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable = [
        "task_name",
        "deadline",
        "description",
        "p_id",
        "t_status",
        
    ];

    public function projectTasks(){
        return $this-> belongsTo(Projects::class,"p_id");
    }

    public function taskStatus(){
        return $this-> belongsTo(TaskStatus::class, "t_status");
    }

    public function taskPriority(){
        return $this-> belongsTo(TaskPriorities::class, "t_priority");
    }

    public function projectParticipants(){
        return $this-> belongsToMany(ProjectParticipants::class, "assigned_tasks", "task_id", "p_participant_id" );
    }

    public function chatMessage(){
        return $this-> hasoOne(ChatMessages::class,"task_id");
    }
}
