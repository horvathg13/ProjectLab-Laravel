<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{
    use HasFactory;
    protected $table = 'projects';

    protected $fillable = [
        'p_manager_id',
        'p_name',
        'p_status',
        'deadline'
    ];

    public function userProject(){
        return $this-> belongsTo(User::class, 'p_manager_id');
    }

    public function projectStatus(){
        return $this->hasOne(ProjectsStatus::class);
    }

    public function tasks(){
        return $this->hasMany(Tasks::class);
    }

    public function chatMessage(){
        return $this->belongsTo(ChatMessages::class, "p_id");
    }
}
