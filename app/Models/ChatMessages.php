<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessages extends Model
{
    use HasFactory;
    protected $table = 'chat_messages';

    protected $fillable = [
        'p_id', 
        'task_id',
        'sender_id',
        'receiver_id'
    ];

    public function users_send(){
        return $this-> hasOne(User::class,"id", "sender_id" );
    }
    public function users_receive(){
        return $this-> hasOne(User::class,"id", "receiver_id" );
    }

    public function task(){
        return $this->hasOne(Task::class);
    }

    public function project(){
        return $this-> hasOne(Projects::class);
    }
}
