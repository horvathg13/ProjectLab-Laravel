<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ProjectsStatus extends Model
{
    use HasFactory;

    protected $table = 'projects_status';

    protected $fillable = [
        'p_status',
    ];

    public function projectStatus(){
        return $this-> belongsTo(Projects::class);
    }
}
