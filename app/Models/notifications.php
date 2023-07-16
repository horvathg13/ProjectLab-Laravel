<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class notifications extends Model
{
    use HasFactory;
    protected $table = 'notifications';
    public $timestamps = true;

    protected $fillable = [
        'p_id',
        'message',
    ];
}
