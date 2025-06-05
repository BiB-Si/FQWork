<?php

namespace Database\Seeders;

use App\Models\User\User;
use Hash;
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
        $admin = [
            'first_name' => 'Никита',
            'last_name' => 'Лепейкин',
            'email' => 'alen2014@gmail.com',
            'password' => Hash::make('123123'),
        ];

        
        $you = [
            'first_name' => 'Алексей',
            'last_name'  => 'Иванов',
            'email'      => 'b.dampilow@gmail.com',
            'password'   => Hash::make('123123'),
        ];


        // Создаём или берём админа и даём роли
        $userAdmin = User::firstOrCreate(
            ['email' => $admin['email']],
            $admin
        );
        $userAdmin->assignRole(['admin', 'user']);

        // Создаём или берём вашего пользователя и даём роль user
        $userYou = User::firstOrCreate(
            ['email' => $you['email']],
            $you
        );
        $userYou->assignRole('user');

        // User::firstOrCreate($you)->addRoles(['user']);
        // (User::firstOrCreate($admin))->addRoles(['admin', 'user']);

    }
}
