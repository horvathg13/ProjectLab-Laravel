<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectParticipants extends Model
{
    use HasFactory;

    protected $table = 'project_participants';

    protected $fillable = [
        'user_id',
        'p_id',
        'p_status',
        
    ];

    public function ParticipantUsers(){
        return $this-> belongsTo(User::class);
    }

    public function tasks(){
        return $this-> belongsToMany(Tasks::class, "assign_tasks", "p_participant_id", "task_id");
    }
}
