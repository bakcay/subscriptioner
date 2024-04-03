<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\Subscription;

class SubscriptionObserver
{
    /**
     * Handle the Subscription "created" event.
     */
    public function created(Subscription $subscription): void
    {
        $ipAddress = app()->bound('requestIp')?app('requestIp'):null;
        Event::create([
            'user_id'    => $subscription->user_id,
            'event'      => 'started' ,
            'ip'         => strlen($ipAddress) > 0 ? $ipAddress : null,
            //'package_id' => $data_payment['packageId'],
        ]);
    }

    /**
     * Handle the Subscription "updated" event.
     */
    public function updated(Subscription $subscription): void
    {
         $ipAddress = app()->bound('requestIp')?app('requestIp'):null;

        $_event_data = [
            'user_id' => $subscription->user_id,
            'event'   => '',
            'ip'      => strlen($ipAddress) > 0 ? $ipAddress : null,
        ];

        if($subscription->isDirty('end_date') ) {
            $_event_data['event'] = 'renewed';
            Event::create($_event_data);
        }

        if($subscription->isDirty('status') && $subscription->status == 'inactive') {
            $_event_data['event'] = 'cancelled';
            Event::create($_event_data);
        }

        if($subscription->isDirty('status') && $subscription->status == 'active') {
            $_event_data['event'] = 'reactivated';
            Event::create($_event_data);
        }

        if($subscription->isDirty('user_count')) {
            if($subscription->user_count > $subscription->getOriginal('user_count')) {
                $_event_data['event'] = 'extended';
            } else {
                $_event_data['event'] = 'shrinked';
            }
            Event::create($_event_data);
        }


    }

    /**
     * Handle the Subscription "deleted" event.
     */
    public function deleted(Subscription $subscription): void
    {
        //
    }

    /**
     * Handle the Subscription "restored" event.
     */
    public function restored(Subscription $subscription): void
    {
        //
    }

    /**
     * Handle the Subscription "force deleted" event.
     */
    public function forceDeleted(Subscription $subscription): void
    {
        //
    }
}
