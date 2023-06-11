<?php

namespace Database\Seeders;

use App\Models\ProjectsStatus;
use Illuminate\Database\Seeder;

class ProjectsStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProjectsStatus::create([
            "p_status" => "Urgent",
        ]);
        ProjectsStatus::create([
            "p_status" => "Suspended",
        ]);
        ProjectsStatus::create([
            "p_status" => "Active",
        ]);
        ProjectsStatus::create([
            "p_status" => "Completed",
        ]);
        ProjectsStatus::create([
            "p_status" => "Deleted",
        ]);
    }
}
