<?php

namespace App\Console\Commands;

use App\Jobs\Subscription\DeactivateService;
use App\Models\User;
use App\Service\ZotloService;
use Illuminate\Console\Command;

class GetSubscriptionCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:cards
                            {userid : User ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets saved cards for a subscription';

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
            $response = ZotloService::getCardList($current_user->subscriber_id);

            if (!isset($response['meta']['httpStatus'])) {
                $this->error("Communication error with service");
                return 1;
            }

            if ($response['meta']['httpStatus'] != 200) {
                $this->error($response['meta']['errorMessage']);
                return 1;
            }

            if (isset($response['result']['cardList']) && count($response['result']['cardList']) > 0) {

                $tabledata = [];

                foreach ($response['result']['cardList'] as $k => $v) {
                    $tabledata[] = [
                        $v['id'],
                        $v['cardNumber'],
                        $v['cardExpire'],
                        $v['createDate'],
                        $v['tokenType'],
                        $v['deletable'],
                    ];
                }

                $this->info("This user have active subscription. And have cards.");
                $this->info("Card Details:");
                $this->table(
                [
                    'ID',
                    'Card Number',
                    'Card Expire',
                    'Create Date',
                    'Type',
                    'deletable',
                ],
                $tabledata);


            }else{
                $this->warn("Cannot access user saved card details.");
            }
        } else {
             $this->warn("This user have not active subscription.");
        }
    }
}
