<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectFiles extends Model
{
    use HasFactory;

    protected $table = 'project_files';

    protected $fillable = [
        'user_id',
        'task_id',
        'file',
        
    ];

    public function ProjectFileToUsers(){
        return $this-> belongsTo(User::class);
    }
}
