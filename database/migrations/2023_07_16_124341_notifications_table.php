<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("p_id");
            $table->unsignedBigInteger("task_id")->nullable();
            $table->string("message");
            $table->foreign("p_id")->references("id")->on("projects");
            $table->foreign('task_id')->references("id")->on('tasks');
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
        //database\migrations\2023_07_16_124341_notifications_table.php
    }
}
