<?php

namespace App\Jobs\Subscription;

use App\Exceptions\SubscriptionException;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Service\ZotloService;

class RescaleService implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

 protected $user_id,$count;
    /**
     * Create a new job instance.
     */
    public function __construct($user_id, $count='')
    {
        $this->user_id = $user_id;
        $this->count = $count;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->user_id);

        $service = ZotloService::updateUserCount($user->subscriber_id, $this->count);

        if ($service['meta']['httpStatus'] != 200) {
            throw new SubscriptionException($service['meta']['errorMessage'], 400);
        }

        $user->activeSubscription->status = 'inactive';
        $user->activeSubscription->save();
    }
}
