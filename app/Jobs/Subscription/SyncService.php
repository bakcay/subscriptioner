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



    /**
     * Create a new job instance.
     */
    public function __construct()
    {

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
        ])->withCount('subscription')
            ->limit(10)
            ->get();

        foreach ($users as $index => $user) {
            /** @var User $user */

            if($user->subscription_count == 0){
                continue;
            }

            $response_old = \Cache::get('subscription_detail_' . $user->subscriber_id);

            $response = ZotloService::getSubscription($user->subscriber_id, true);

            if (!isset($response['meta']['httpStatus'])) {
                throw new SubscriptionException('Communication error with service', 400);
            }
            if ($response['meta']['httpStatus'] != 200) {
                throw new SubscriptionException($response['meta']['errorMessage'], 400);
            }




            if ($response['result']['profile']['realStatus'] == 'active' && $user->subscription->status != 'active') {
                $user->subscription->status     = 'active';
                $user->subscription->start_date = $response['result']['profile']['startDate'];
                $user->subscription->end_date   = $response['result']['profile']['expireDate'];
                $user->subscription->save();
            }

            if ($response['result']['profile']['realStatus'] == 'active' && $response['result']['profile']['expireDate'] != $user->subscription->end_date){
                $user->subscription->end_date   = $response['result']['profile']['expireDate'];
                $user->subscription->save();
            }

            if ($response['result']['profile']['realStatus'] != 'active' && $user->subscription->status == 'active') {
                $user->subscription->status = 'inactive';
                $user->subscription->save();
            }

            if ($response['result']['profile']['quantity'] != $user->subscription->user_count) {
                $user->subscription->user_count = $response['result']['profile']['quantity'];
                $user->subscription->save();
            }
        }
    }
}
