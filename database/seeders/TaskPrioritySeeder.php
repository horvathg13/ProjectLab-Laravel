<?php

namespace Database\Seeders;

use App\Models\TaskPriorities;
use Illuminate\Database\Seeder;

class TaskPrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TaskPriorities::create([
            "task_priority" => "High",
        ]);
        TaskPriorities::create([
            "task_priority" => "Medium",
        ]);
        TaskPriorities::create([
            "task_priority" => "Low",
        ]);
       
    }
}
