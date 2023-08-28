<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            "password"=>Hash::make("admin"),
            "status"=>"active",
        ]);
        User::create([
            "name"=>"manager",
            "email"=>"manager@projectlab.hu",
            "password"=>Hash::make("manager"),
            "status"=>"active",
        ]);
        User::create([
            "name"=>"employee",
            "email"=>"employee@projectlab.hu",
            "password"=>Hash::make("employee"),
            "status"=>"active",
        ]);
        
       
        
    }
}
