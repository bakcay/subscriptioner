<?php

namespace App\Http\Controllers;

use App\Exceptions\SubscriptionException;
use App\Jobs\Subscription\DeactivateService;
use App\Jobs\Subscription\ReactivateService;
use App\Jobs\Subscription\RescaleService;
use App\Models\User;
use Illuminate\Http\Request;

class HookController extends Controller
{
    public function handleSubscriberUpdate(Request $request)
    {
        $queue_type = $request->input('queue.type');

        if ($queue_type === 'SubscriberUpdate') {

            $subscriber_id = $request->input('parameters.profile.subscriberId');
            $event_type = $request->input('queue.eventType');



            $user = User::with(['subscription'])->withCount('subscription')->where('subscriber_id', $subscriber_id)->first();

            if(!$user){
                throw new SubscriptionException('User not found', 404);
            }

            if($user->subscription_count === 0){
                throw new SubscriptionException('User has no subscription', 404);
            }

            switch ($event_type){
                case 'cancel':
                    $user->subscription->status = 'inactive';
                    break;
                case 'reactivate':
                    $user->subscription->status = 'active';
                    break;
                case 'packageUpgrade':
                case 'packageDowngrade':
                   $user->subscription->user_count = $request->input('parameters.profile.quantity');
                    break;
                default:
                    $user->subscription->status     = $request->input('parameters.profile.realStatus') == 'active' ? 'active' : 'inactive';
                    $user->subscription->user_count = $request->input('parameters.profile.quantity');
                    $user->subscription->start_date = $request->input('parameters.profile.startDate');
                    $user->subscription->end_date   = $request->input('parameters.profile.expireDate');

                    break;

            }




            $user->subscription->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription updated successfully',
                'subscription' => $user->subscription,
            ]);

        }


    }
}
