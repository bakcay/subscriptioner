<?php

namespace App\Http\Controllers;

use App\Exceptions\SubscriptionException;
use App\Http\Requests\CreateSubscriptionRequest;
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
                //Here trigger an event to notify cache or database
                return response()->json([
                    'status'                   => 'success',
                    'have_active_subscription' => false,
                ]);
            } else {
                return response()->json([
                    'status'                   => 'success',
                    'have_active_subscription' => true,
                    'subscription'             => $response['result']['profile'],
                    //'db_record'=> $current_user->activeSubscription
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

        Event::create([
            'user_id'    => $user_id,
            'event'      => $response_payment['meta']['httpStatus'] == 200 ? 'started' : 'failed',
            'ip'         => $request->ip(),
            'package_id' => $data_payment['packageId'],
        ]);

        if ($response_payment['meta']['httpStatus'] == '200') {
            $_profile = $response_payment['result']['profile'];

            Subscription::create([
                'user_id'    => $user_id,
                'status'     => 'active',
                'start_date' => $_profile['startDate'],
                'end_date'   => $_profile['renewalDate'],
            ]);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Subscription created successfully',
                'payment'      => [
                    'startDate'   => $_profile['startDate'],
                    'renewalDate' => $_profile['renewalDate']
                ],
                'subscriberId' => $current_user->subscriber_id
            ]);
        } else {
            throw new SubscriptionException($response_payment['meta']['errorMessage'], 400);
        }
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
