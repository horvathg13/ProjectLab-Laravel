<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatView extends Model
{
    use HasFactory;

    protected $table = 'chat_messages_viewing';
    public $timestamps = true;

    protected $fillable = [
        'chat_id', 
    ];

    
}
