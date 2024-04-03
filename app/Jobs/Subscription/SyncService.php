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

class SyncService implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user_id;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = User::with([
            'subscription' => function ($query) {
                $query->where('last_check', '<', now()->subMinutes(2));
            }
        ])->get();

        foreach ($users as $index => $user) {
            /** @var User $user */

            $response_old = \Cache::get('subscription_detail_' . $user->subscriber_id);

            $response = ZotloService::getSubscription($user->subscriber_id, true);

            if (!isset($response['meta']['httpStatus'])) {
                throw new SubscriptionException('Communication error with service', 400);
            }
            if ($response['meta']['httpStatus'] != 200) {
                throw new SubscriptionException($response['meta']['errorMessage'], 400);
            }

            if($response_old['result']['profile']['realStatus'] == $response['result']['profile']['realStatus']){

            }


            if ($response['result']['profile']['realStatus'] == 'active' && $user->activeSubscription->status != 'active') {
                $user->activeSubscription->status     = 'active';
                $user->activeSubscription->start_date = $response['result']['profile']['startDate'];
                $user->activeSubscription->end_date   = $response['result']['profile']['expireDate'];
                $user->activeSubscription->save();
            }

            if ($response['result']['profile']['realStatus'] == 'active' && $response['result']['profile']['expireDate'] != $user->activeSubscription->end_date){
                $user->activeSubscription->end_date   = $response['result']['profile']['expireDate'];
                $user->activeSubscription->save();
            }

            if ($response['result']['profile']['realStatus'] != 'active' && $user->activeSubscription->status == 'active') {
                $user->activeSubscription->status = 'inactive';
                $user->activeSubscription->save();
            }

            if ($response['result']['profile']['quantity'] != $user->activeSubscription->user_count) {
                $user->activeSubscription->user_count = $response['result']['profile']['quantity'];
                $user->activeSubscription->save();
            }
        }
    }
}
