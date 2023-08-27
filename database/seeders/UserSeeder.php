<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create(
        [
            "name"=>"admin",
            "email"=>"admin@projectlab.hu",
            "password"=>"admin",
            "status"=>"active",
        ]);
        User::create([
            "name"=>"manager",
            "email"=>"manager@projectlab.hu",
            "password"=>"manager",
            "status"=>"active",
        ]);
        User::create([
            "name"=>"employee",
            "email"=>"employee@projectlab.hu",
            "password"=>"employee",
            "status"=>"active",
        ]);
        
       
        
    }
}
