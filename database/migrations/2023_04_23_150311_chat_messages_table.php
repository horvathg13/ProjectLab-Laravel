<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("p_id");
            $table->foreign("p_id")->references("id")->on("projects");
            $table->unsignedBigInteger("task_id")->nullable();
            $table->foreign("task_id")->references("id")->on("tasks");
            $table->unsignedBigInteger("sender_id");
            $table->foreign("sender_id")->references("id")->on("users");
            $table->text("message");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}
