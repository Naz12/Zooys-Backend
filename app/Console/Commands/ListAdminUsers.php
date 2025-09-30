<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class ListAdminUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admins = Admin::all(['id', 'name', 'email', 'created_at']);

        if ($admins->isEmpty()) {
            $this->info('No admin users found.');
            return 0;
        }

        $this->info('Admin Users:');
        $this->line('');

        foreach ($admins as $admin) {
            $this->line("ID: {$admin->id}");
            $this->line("Name: {$admin->name}");
            $this->line("Email: {$admin->email}");
            $this->line("Created: {$admin->created_at}");
            $this->line('---');
        }

        return 0;
    }
}
