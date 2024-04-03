<?php
/**
 * Created by PhpStorm.
 * User: esh
 * Project name subscriptioner
 * 2.04.2024 15:10
 * Bünyamin AKÇAY <bunyamin@bunyam.in>
 */

namespace App\Service;

use Cache;
use Http;

class ZotloService
{

    public static function getSubscription($subscriptionId,$resync = false)
    {

        if($resync){
            Cache::forget('subscription_detail_'.$subscriptionId);
        }

        return Cache::remember('subscription_detail_'.$subscriptionId, 60, function () use ($subscriptionId) {
            $resp =  Http::withHeaders([
                'Content-Type'  => 'application/json',
                'AccessKey'     => config('zotlo.access_key'),
                'AccessSecret'  => config('zotlo.access_secret'),
                'ApplicationId' => config('zotlo.application_id'),
                'Language'      => config('zotlo.language'),
            ])
              ->get(config('zotlo.base_url') . '/v1/subscription/profile' ,['subscriberId'=>$subscriptionId,'packageId'=>config('zotlo.package_id')])
              ->json();
            $resp['lastCheck'] = now()->format('Y-m-d H:i:s');
            return $resp;
        });


    }

    public static function updateEmail($subscriptionId,$email)
    {
        return Http::withHeaders([
            'Content-Type'  => 'application/json',
            'AccessKey'     => config('zotlo.access_key'),
            'AccessSecret'  => config('zotlo.access_secret'),
            'ApplicationId' => config('zotlo.application_id'),
            'Language'      => config('zotlo.language'),
        ])
          ->post(config('zotlo.base_url') . '/v1/subscription/update-email' ,['subscriberId'=>$subscriptionId,'email'=>$email])
          ->json();
    }

    public static function updateUserCount($subscriptionId, $userCount)
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'AccessKey' => config('zotlo.access_key'),
            'AccessSecret' => config('zotlo.access_secret'),
            'ApplicationId' => config('zotlo.application_id'),
            'Language' => config('zotlo.language'),
        ])
        ->post(config('zotlo.base_url') . '/v1/subscription/change-quantity', [
            'subscriberId' => $subscriptionId,
            'packageId'    => config('zotlo.package_id'),
            'quantity'     => $userCount
        ])
        ->json();
    }

    public static function reactivateSubscription($subscriptionId)
    {
        Cache::forget('subscription_detail_'.$subscriptionId);
        return Http::withHeaders([
            'Content-Type'  => 'application/json',
            'AccessKey'     => config('zotlo.access_key'),
            'AccessSecret'  => config('zotlo.access_secret'),
            'ApplicationId' => config('zotlo.application_id'),
            'Language'      => config('zotlo.language'),
        ])
        ->post(config('zotlo.base_url') . '/v1/subscription/reactivate',
            [
                'subscriberId' => $subscriptionId,
                'packageId' => config('zotlo.package_id')
            ])
        ->json();
    }

    public static function cancelSubscription($subscriptionId,$reason = '')
    {
        Cache::forget('subscription_detail_'.$subscriptionId);
        return Http::withHeaders([
            'Content-Type'  => 'application/json',
            'AccessKey'     => config('zotlo.access_key'),
            'AccessSecret'  => config('zotlo.access_secret'),
            'ApplicationId' => config('zotlo.application_id'),
            'Language'      => config('zotlo.language'),
        ])
        ->post(config('zotlo.base_url') . '/v1/subscription/cancellation',
            [
                'subscriberId' => $subscriptionId,
                'packageId' => config('zotlo.package_id'),
                'cancellationReason'=>$reason
            ])
        ->json();
    }

    public static function makePayment(array $data)
    {
        return Http::withHeaders([
            'Content-Type'  => 'application/json',
            'AccessKey'     => config('zotlo.access_key'),
            'AccessSecret'  => config('zotlo.access_secret'),
            'ApplicationId' => config('zotlo.application_id'),
            'Language'      => config('zotlo.language'),
        ])
          ->post(config('zotlo.base_url') . '/v1/payment/credit-card', $data)
          ->json();
    }

    public static function getCardList($subscriptionId)
    {
        return Http::withHeaders([
            'Content-Type'  => 'application/json',
            'AccessKey'     => config('zotlo.access_key'),
            'AccessSecret'  => config('zotlo.access_secret'),
            'ApplicationId' => config('zotlo.application_id'),
            'Language'      => config('zotlo.language'),
        ])
          ->get(config('zotlo.base_url') . '/v1/subscription/card-list' ,['subscriberId'=>$subscriptionId])
          ->json();
    }

}
