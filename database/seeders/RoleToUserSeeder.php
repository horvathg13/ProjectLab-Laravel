<?php

namespace Database\Seeders;

use App\Models\RoleToUser;
use Illuminate\Database\Seeder;

class RoleToUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RoleToUser::create(
        [
            "role_id"=>1,
            "user_id"=>1,
        ]);
        RoleToUser::create([
            "role_id"=>2,
            "user_id"=>2,
        ]);
        RoleToUser::create([
            "role_id"=>3,
            "user_id"=>3,
        ]);
        
    }
}
