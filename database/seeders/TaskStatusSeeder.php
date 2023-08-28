<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        TaskStatus::create([
            "task_status" => "Suspended",
        ]);
        TaskStatus::create([
            "task_status" => "Active",
        ]);
        TaskStatus::create([
            "task_status" => "Completed",
        ]);
        TaskStatus::create([
            "task_status" => "Accepted",
        ]);
    }
}
