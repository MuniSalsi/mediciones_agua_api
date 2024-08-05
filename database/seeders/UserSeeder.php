<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'email' => 'sistemas@salsipuedes.gob.ar',
            'password' => Hash::make('S@ls1pu3d3s'),
        ]);
    }
}
