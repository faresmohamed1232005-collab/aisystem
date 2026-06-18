<?php
// database/seeders/SuperAdminSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL')],
            [
                'name'          => 'Super Admin',
                'email'         => env('SUPER_ADMIN_EMAIL'),
                'password'      => Hash::make(env('SUPER_ADMIN_PASSWORD')),
                'phone'         => '01000000000',
                'pharmacy_name' => 'AI Pharmacy Admin',
                'address'       => 'Admin',
                'governorate'   => 'Admin',
                'city'          => 'Admin',
                'is_approved'   => true,
            ]
        );
    }
}