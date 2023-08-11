<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteProjects extends Model
{
    use HasFactory;

    protected $table = 'favorite_projects';
    public $timestamps = true;

    protected $fillable = [
        'added_by',
        'project_id',
        
        
    ];
}
