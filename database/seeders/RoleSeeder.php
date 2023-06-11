<?php

namespace Database\Seeders;

use App\Models\Roles ;
use Illuminate\Database\Seeder;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Roles::create([
            'role_name'=>'Admin',
        ]);
        Roles::create([
            'role_name'=>'Manager',
            
        ]);
        Roles::create([
            'role_name'=>'Employee'
        ]);
    }
}
