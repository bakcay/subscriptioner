<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Subscription;
use App\Models\User;
use App\Service\ZotloService;
use Illuminate\Console\Command;

class CreateSubscription extends Command
{
    protected $signature = 'subscription:create
                            {userid : User ID}
                            {ccno : The credit card number}
                            {expire : The expiration date (MM/YY)}
                            {cvv : The CVV number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new subscription';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userid = $this->argument('userid');
        $ccno   = $this->argument('ccno');
        $expire = $this->argument('expire');
        $cvv    = $this->argument('cvv');


        $current_user = User::withCount(['subscription'])->with('subscription')
                            ->find($userid);

        if(!isset($current_user)) {
            $this->error("User not found");
            return 1;
        }

        if ($current_user->subscription_count > 0) {
            $this->error("There is already an subscription for this user.");

            if($current_user->subscription->status == 'active') {
                $this->error("The subscription is already active.");
            } else {
                $this->error("The subscription is not active. Requires reactivation.");
            }
            return 1;
        }

        // UserID kontrolü
        if (empty($userid) || !is_numeric($userid)) {
            $this->error("The userid is required and must be numeric.");
            return 1;
        }

        // Kredi Kartı Numarası Kontrolü
        if (empty($ccno) || !is_numeric($ccno) || strlen($ccno) != 16) {
            $this->error("The ccno is required, must be numeric and 16 digits long.");
            return 1;
        }

        // Son Kullanma Tarihi Kontrolü
        $expireRegex = '/^(0[1-9]|1[0-2])\/?([0-9]{2})$/';
        if (empty($expire) || !preg_match($expireRegex, $expire)) {
            $this->error("The expire is required and must be in MM/YY format.");
            return 1;
        }

        // CVV Kontrolü
        if (empty($cvv) || !is_numeric($cvv) || (strlen($cvv) != 3 && strlen($cvv) != 4)) {
            $this->error("The cvv is required, must be numeric and either 3 or 4 digits long.");
            return 1;
        }

        $parts     = explode(' ', trim($current_user->name));
        $lastname  = array_pop($parts);
        $firstname = implode(' ', $parts);
        $expires   = explode('/', $expire);

        $data_payment = [
            'cardOwner'             => $current_user->name,
            'cardNo'                => $ccno,
            'expireMonth'           => $expires[0],
            'expireYear'            => $expires[1],
            'cvv'                   => $cvv,
            'subscriberPhoneNumber' => $current_user->phone,
            'subscriberEmail'       => $current_user->email,
            'subscriberFirstname'   => $firstname,
            'subscriberLastname'    => $lastname,
            'subscriberId'          => $current_user->subscriber_id,
            'subscriberIpAddress'   => '0.0.0.0',
            'subscriberCountry'     => $current_user->country,
            'language'              => $current_user->country,
            'installment'           => 1,
            'quantity'              => 1,
            'useWallet'             => false,
            'packageId'             => config('zotlo.package_id'),
        ];

        $response_payment = ZotloService::makePayment($data_payment);

        if (!isset($response_payment['meta']['httpStatus'])) {
            $this->error("Communication error with service");
            return 1;
        }


        if ($response_payment['meta']['httpStatus'] == '200') {
            $_profile = $response_payment['result']['profile'];

            Subscription::create([
                'user_id'    => $userid,
                'status'     => 'active',
                'start_date' => $_profile['startDate'],
                'end_date'   => $_profile['expireDate'],
                'user_count' => $_profile['quantity'],
            ]);

            $this->info("Subscription created successfully");
            $this->info("Start Date: {$_profile['startDate']}");
            $this->info("Expire Date: {$_profile['expireDate']}");
        } else {
            Event::create([
                'user_id' => $userid,
                'event'   => 'failed',
                'ip'      => null,
            ]);
            $this->error($response_payment['meta']['errorMessage']);
            return 1;
        }
    }
}
