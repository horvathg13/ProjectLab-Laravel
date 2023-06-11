<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string("task_name",255);
            $table->date("deadline");
            $table->text("description");
            $table->unsignedBigInteger("p_id");
            $table->foreign("p_id")->references("id")->on("projects");
            $table->unsignedBigInteger("t_status");
            $table->foreign("t_status")->references("id")->on("task_status");
            $table->unsignedBigInteger("t_priority");
            $table->foreign("t_priority")->references("id")->on("task_priorities");
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
        Schema::dropIfExists('tasks');
    }
}
