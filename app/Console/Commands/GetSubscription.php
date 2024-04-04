<?php

namespace App\Console\Commands;

use App\Jobs\Subscription\DeactivateService;
use App\Models\User;
use App\Service\ZotloService;
use Illuminate\Console\Command;

class GetSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:get
                            {userid : User ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets detail for a subscription';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userid = $this->argument('userid');
        $current_user = User::withCount(['activeSubscription'])
                            ->with(['activeSubscription'])
                            ->find($userid);


        if ($current_user->active_subscription_count > 0) {
            $response = ZotloService::getSubscription($current_user->subscriber_id);

            if (!isset($response['meta']['httpStatus'])) {
                $this->error("Communication error with service");
                return 1;
            }

            if ($response['meta']['httpStatus'] != 200) {
                $this->error($response['meta']['errorMessage']);
                return 1;
            }

            if ($response['result']['profile']['realStatus'] != 'active') {
                DeactivateService::dispatch($current_user->id);

                $this->warn("This user have not active subscription. The subscription is not active. Requires reactivation.");
            } else {

                $this->info("This user have active subscription.");
                $this->info("Subscription Details:");
                $this->table(
                [
                    'Email',
                    'Subscription ID',
                    'Subscription Status',
                    'Subscription Start Date',
                    'Subscription End Date',
                    'Subscription Quantity'
                ], [
                    [
                        $current_user->email,
                        $response['result']['profile']['subscriptionId'],
                        $response['result']['profile']['realStatus'],
                        $response['result']['profile']['startDate'],
                        $response['result']['profile']['expireDate'],
                        $response['result']['profile']['quantity']
                    ]
                ]);


            }
        } else {
             $this->warn("This user have not active subscription.");
        }
    }
}
