<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FavoriteProjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorite_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("added_by");
            $table->foreign("added_by")->references("id")->on("users");
            $table->unsignedBigInteger("project_id");
            $table->foreign("project_id")->references("id")->on("projects");
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
        //
    }
}
