<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {name} {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');

        // Check if admin already exists
        if (Admin::where('email', $email)->exists()) {
            $this->error("Admin with email {$email} already exists!");
            return 1;
        }

        // Create the admin user
        $admin = Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Admin user created successfully!");
        $this->line("Name: {$admin->name}");
        $this->line("Email: {$admin->email}");
        $this->line("ID: {$admin->id}");

        return 0;
    }
}
