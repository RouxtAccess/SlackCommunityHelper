<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAllUsersAppHomePage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?Workspace $workspace;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Workspace $workspace = null)
    {
        $this->workspace = $workspace;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->workspace)
        {
            $users = $this->workspace->users;
        }
        else
        {
            $users = User::all();
        }
        foreach ($users as $user)
        {
            UpdateSlackAppHomeTab::dispatch($user);
        }
    }
}
