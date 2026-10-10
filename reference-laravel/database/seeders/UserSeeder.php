<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Creates the four accounts with random passwords, written once to storage/app/first-logins.txt */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $host  = parse_url(config('app.url'), PHP_URL_HOST) ?: 'school.local';
        $lines = [];

        foreach (['bursar' => 'Bursar', 'director' => 'Director', 'deputy' => 'Deputy director', 'auditor' => 'Auditor'] as $name => $role) {
            $email = "$name@$host";
            $pass  = Str::random(14);
            $u = User::firstOrNew(['email' => $email]);
            if ($u->exists) { continue; }              // never overwrite an existing account
            $u->name = $role;
            $u->password = Hash::make($pass);
            $u->role = $role;
            $u->save();
            $lines[] = "$role: $email  password: $pass";
        }

        if ($lines) {
            file_put_contents(storage_path('app/first-logins.txt'), implode("\n", $lines)."\n");
        }
    }
}
