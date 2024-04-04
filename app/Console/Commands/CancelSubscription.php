<?php

namespace App\Console\Commands;

use App\Jobs\Subscription\DeactivateService;
use App\Models\User;
use App\Service\ZotloService;
use Illuminate\Console\Command;

class CancelSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:cancel
                            {userid : User ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancels Subscription';

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

            if ($response['result']['profile']['realStatus'] == 'active') {
                DeactivateService::dispatch($current_user->id);

                $this->info("Successfully queued for cancellation the subscription for user : ".$current_user->email);

            } else {

                $this->warn("This user have not active subscription.");

            }
        } else {
             $this->warn("This user have not any subscription.");
        }
    }
}
