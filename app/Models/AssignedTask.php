<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedTask extends Model
{
    use HasFactory;

    protected $table = 'assigned_tasks';

    protected $fillable = [
        'task_id',
        'p_participant_id',
    ];
}
