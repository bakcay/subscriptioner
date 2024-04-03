<?php

namespace App\Http\Controllers;

use App\Exceptions\SubscriptionException;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Jobs\Subscription\DeactivateService;
use App\Jobs\Subscription\ReactivateService;
use App\Jobs\Subscription\RescaleService;
use App\Jobs\Subscription\SyncService;
use App\Models\Event;
use App\Models\Subscription;
use App\Models\User;
use App\Service\ZotloService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{


    public function getSubscription()
    {
        $user_id      = auth()->user()->id;
        $current_user = User::withCount(['activeSubscription'])
                            ->with(['activeSubscription'])
                            ->find($user_id);


        if ($current_user->active_subscription_count > 0) {
            $response = ZotloService::getSubscription($current_user->subscriber_id);

            if (!isset($response['meta']['httpStatus'])) {
                throw new SubscriptionException('Communication error with service', 400);
            }

            if ($response['meta']['httpStatus'] != 200) {
                throw new SubscriptionException($response['meta']['errorMessage'], 400);
            }

            if ($response['result']['profile']['realStatus'] != 'active') {
                DeactivateService::dispatch($current_user->id);

                return response()->json([
                    'status'                   => 'success',
                    'have_active_subscription' => false,
                ]);
            } else {
                return response()->json([
                    'status'                   => 'success',
                    'have_active_subscription' => true,
                    'subscription'             => $response['result']['profile'],
                ]);
            }
        } else {
            return response()->json([
                'status'                   => 'success',
                'have_active_subscription' => false
            ]);
        }
    }

    public function createSubscription(CreateSubscriptionRequest $request)
    {
        $user_id = auth()->user()->id;

        $current_user = User::withCount(['activeSubscription'])
                            ->find($user_id);

        if ($current_user->active_subscription_count > 0) {
            throw new SubscriptionException('You already have an active subscription', 400);
        }

        $data_payment = $this->preparePaymentData($current_user, $request);

        $response_payment = ZotloService::makePayment($data_payment);

        if (!isset($response_payment['meta']['httpStatus'])) {
            throw new SubscriptionException('Communication error with service', 400);
        }


        if ($response_payment['meta']['httpStatus'] == '200') {
            $_profile = $response_payment['result']['profile'];

            Subscription::create([
                'user_id'    => $user_id,
                'status'     => 'active',
                'start_date' => $_profile['startDate'],
                'end_date'   => $_profile['expireDate'],
                'user_count' => $_profile['quantity'],
            ]);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Subscription created successfully',
                'payment'      => [
                    'startDate'  => $_profile['startDate'],
                    'expireDate' => $_profile['expireDate']
                ],
                'subscriberId' => $current_user->subscriber_id,
                'token'        => \Auth::tokenById($user_id)
            ]);
        } else {
            Event::create([
                'user_id' => $user_id,
                'event'   => 'failed',
                'ip'      => $request->ip(),
            ]);
            throw new SubscriptionException($response_payment['meta']['errorMessage'], 400);
        }
    }

    public function cancelSubscription()
    {
        $user_id      = auth()->user()->id;
        $current_user = User::withCount(['activeSubscription'])
                            ->with(['activeSubscription'])
                            ->find($user_id);

        if ($current_user->active_subscription_count == 0) {
            throw new SubscriptionException('You do not have an active subscription', 400);
        }


        /*
         * Without queue
         $response = ZotloService::cancelSubscription($current_user->subscriber_id, 'User request');

        if (!isset($response['meta']['httpStatus'])) {
            throw new SubscriptionException('Communication error with service', 400);
        }

        if ($response['meta']['httpStatus'] == '200') {
            $current_user->activeSubscription->status = 'inactive';
            $current_user->activeSubscription->last_notify = now();
            $current_user->activeSubscription->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Subscription cancelled successfully',
            ]);
        } else {
            throw new SubscriptionException($response['meta']['errorMessage'], 400);
        }

         */


        DeactivateService::dispatch($user_id, 'User request');

        return response()->json([
            'status'  => 'success',
            'message' => 'Deactivation queued successfully',
        ]);
    }

    public function reactivateSubscription()
    {
        $user_id      = auth()->user()->id;
        $current_user = User::withCount(['subscription'])
                            ->with(['subscription'])
                            ->find($user_id);

        if ($current_user->subscription_count != 1 ) {
            throw new SubscriptionException('There is no subscription', 400);
        }

        if($current_user->subscription->status == 'active'){
            throw new SubscriptionException('Subscription is already active', 400);
        }


        /*
         Without queue
        $response = ZotloService::reactivateSubscription($current_user->subscriber_id);

        if (!isset($response['meta']['httpStatus'])) {
            throw new SubscriptionException('Communication error with service', 400);
        }

        if ($response['meta']['httpStatus'] == '200') {
            $current_user->activeSubscription->status = 'active';
            $current_user->activeSubscription->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Subscription reactivated successfully',
            ]);
        } else {
            throw new SubscriptionException($response['meta']['errorMessage'], 400);
        }
        */


        ReactivateService::dispatch($user_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Reactivation queued successfully',
        ]);
    }

    public function rescaleSubscription(Request $request)
    {
        $user_id      = auth()->user()->id;
        $current_user = User::withCount(['subscription'])
                            ->with(['subscription'])
                            ->find($user_id);

        if ($current_user->subscription_count == 0) {
            throw new SubscriptionException('There is no active subscription', 400);
        }

        if($current_user->subscription->status != 'active'){
            throw new SubscriptionException('Subscription is not active', 400);
        }

        $count = $request->input('count',0);

        if ($count<1) {
            throw new SubscriptionException('Count cannot be lower than 1', 400);
        }

        RescaleService::dispatch($user_id, $count);

        return response()->json([
            'status'  => 'success',
            'message' => 'Rescale queued successfully',
        ]);
    }

    public function getCardList()
    {

        $user_id = auth()->user()->id;
        $current_user = User::find($user_id);

        $response = ZotloService::getCardList($current_user->subscriber_id);

        if (!isset($response['meta']['httpStatus'])) {
            throw new SubscriptionException('Communication error with service', 400);
        }

        if ($response['meta']['httpStatus'] != 200) {
            throw new SubscriptionException($response['meta']['errorMessage'], 400);
        }

        return response()->json([
            'status' => 'success',
            'saved_card_count'=> count($response['result']['cardList']),
            'cards' => $response['result']['cardList']
        ]);
    }

    protected function preparePaymentData($current_user, $request)
    {
        /**
         * @var User $current_user
         * @var Request $request
         */
        list($firstname, $lastname) = $this->splitName($current_user->name);
        $data_payment = [
            'cardOwner'             => $request->input('card_owner') ?? $current_user->name,
            'cardNo'                => $request->input('credit_card'),
            'expireMonth'           => $request->input('expire_month'),
            'expireYear'            => $request->input('expire_year'),
            'cvv'                   => $request->input('cvv'),
            'subscriberPhoneNumber' => $current_user->phone,
            'subscriberEmail'       => $current_user->email,
            'subscriberFirstname'   => $firstname,
            'subscriberLastname'    => $lastname,
            'subscriberId'          => $current_user->subscriber_id,
            'subscriberIpAddress'   => $request->ip(),
            'subscriberCountry'     => $current_user->country,
            'language'              => $current_user->country,
            'installment'           => 1,
            'quantity'              => 1,
            'useWallet'             => false,
            'packageId'             => config('zotlo.package_id'),
        ];
        return $data_payment;
    }


    protected function splitName($fullName)
    {
        $parts     = explode(' ', trim($fullName));
        $lastname  = array_pop($parts);
        $firstname = implode(' ', $parts);
        return [$firstname, $lastname];
    }

}
