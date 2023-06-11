<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RoleSeeder::class);
        $this->call(ProjectsStatusSeeder::class);
        $this->call(TaskStatusSeeder::class);
        $this->call(TaskPrioritySeeder::class);
    }
}
