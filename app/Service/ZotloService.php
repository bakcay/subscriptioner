<?php
/**
 * Created by PhpStorm.
 * User: esh
 * Project name subscriptioner
 * 2.04.2024 15:10
 * Bünyamin AKÇAY <bunyamin@bunyam.in>
 */

namespace App\Service;

class ZotloService
{

    public static function getSubscription($subscriptionId)
    {
        return \Http::withHeaders([
            'Content-Type'  => 'application/json',
            'AccessKey'     => config('zotlo.access_key'),
            'AccessSecret'  => config('zotlo.access_secret'),
            'ApplicationId' => config('zotlo.application_id'),
            'Language'      => config('zotlo.language'),
        ])
          ->get(config('zotlo.base_url') . '/v1/subscription/profile' ,['subscriberId'=>$subscriptionId,'packageId'=>config('zotlo.package_id')])
          ->json();
    }

    public static function makePayment(array $data)
    {
        return \Http::withHeaders([
            'Content-Type'  => 'application/json',
            'AccessKey'     => config('zotlo.access_key'),
            'AccessSecret'  => config('zotlo.access_secret'),
            'ApplicationId' => config('zotlo.application_id'),
            'Language'      => config('zotlo.language'),
        ])
          ->post(config('zotlo.base_url') . '/v1/payment/credit-card', $data)
          ->json();
    }

}
