<?php

namespace App\Jobs\Subscription;

use App\Exceptions\SubscriptionException;
use App\Models\User;
use App\Service\ZotloService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeactivateService implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user_id,$reason;
    /**
     * Create a new job instance.
     */
    public function __construct($user_id, $reason='')
    {
        $this->user_id = $user_id;
        $this->reason = $reason;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->user_id);

        $service = ZotloService::cancelSubscription($user->subscriber_id, $this->reason);

        if ($service['meta']['httpStatus'] != 200) {
            throw new SubscriptionException($service['meta']['errorMessage'], 400);
        }

        $user->activeSubscription->status = 'inactive';
        $user->activeSubscription->save();
    }
}
