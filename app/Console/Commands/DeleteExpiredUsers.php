<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DeleteExpiredUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-expired-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to delete the users after 30 days';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $users = User::onlyTrashed()->where('deletion_scheduled_at', '<=', now())->get();

        foreach ($users as $user) {
            $user->forceDelete();
        }

        $this->info("Expired users deleted successfully.");
    }


}
