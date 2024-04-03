<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSubscriptionRequest;
use App\Models\Event;
use App\Models\Subscription;
use App\Models\User;
use App\Service\ZotloService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller {


    public function createSubscription(CreateSubscriptionRequest $request) {


        $user_id = auth()->user()->id;

        $current_user = User::withCount(['activeSubscription'])->find($user_id);

        if ($current_user->active_subscription_count > 0) {
            return response()->json(['error' => 'You already have an active subscription'], 400);
        }

        $nameparts = explode(' ', trim(auth()->user()->name));
        $lastname  = array_pop($nameparts);
        $firstname = implode(' ', $nameparts);

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

        $response_payment = ZotloService::makePayment($data_payment);



        if($response_payment['meta']['httpStatus']=='200'){

            $_profile = $response_payment['result']['profile'];

            $data_subscription = [
                'user_id'         => $user_id,
                'status'          => 'active',
                'start_date'      => $_profile['startDate'],
                'end_date'        => $_profile['renewalDate'],
            ];

            Subscription::create($data_subscription);

            $api_response = [
                'status'  => 'success',
                'message' => 'Subscription created successfully',
                'payment' => [
                    'startDate'   => $_profile['startDate'],
                    'renewalDate' => $_profile['renewalDate']
                ],
                'subscriberId' => $current_user->subscriber_id
            ];
            $api_http_status = 200;

        }else{

            $api_response = [
                'status'  => 'error',
                'message' => $response_payment['meta']['errorMessage'],
            ];
            $api_http_status = 400;
        }


        Event::create([
            'user_id'    => $user_id,
            'event'      => $response_payment['meta']['httpStatus']=='200'?'started':'failed',
            'ip'         => $request->ip(),
            'package_id' => $data_payment['packageId'],
        ]);


        return response()->json($api_response, $api_http_status);


    }

}
