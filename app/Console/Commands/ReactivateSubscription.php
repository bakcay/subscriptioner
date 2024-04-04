<?php

namespace App\Console\Commands;

use App\Jobs\Subscription\ReactivateService;
use App\Models\User;
use App\Service\ZotloService;
use Illuminate\Console\Command;

class ReactivateSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:reactivate
                            {userid : User ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reactivates a subscription';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userid = $this->argument('userid');
        $current_user = User::withCount(['subscription'])
                            ->with(['subscription'])
                            ->find($userid);


        if ($current_user->subscription_count > 0) {
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
                ReactivateService::dispatch($current_user->id);

                $this->info("Successfully queued for reactivation the subscription for user : ".$current_user->email);

            } else {

                $this->warn("This user have not passive subscription.");

            }
        } else {
             $this->warn("This user have not any subscription.");
        }
    }
}
