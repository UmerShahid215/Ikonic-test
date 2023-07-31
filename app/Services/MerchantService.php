<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use http\Env\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

class MerchantService
{
    public function __construct(
        protected ApiService $apiService,
        protected Affiliate $affiliate,
        protected Merchant $merchant,
        protected Order $order,
        protected User $user
    ) {}
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {

        $user =  $this->user->create([
            'email' => $data['email'],
            'name' => isset($data['name']) ? $data['name'] : null,
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT,
        ]);
        $merchant = $this->merchant->create([
            'domain' => $data['domain'],
            'display_name' => $data['name'],
            'user_id' => $user->id
        ]);
        return $merchant;
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $merchant = $user->merchant;
        $merchant->update([
            'domain' => $data['domain'],
            'display_name' => $data['name'],
        ]);
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        /**
         * If we look up for user first it will take 2 queries but the method used will take one query to achieve
         * desired result both methods are good but i usually prefer this one.
         */
        return $this->merchant->whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $unpaidOrders = $this->order->where('affiliate_id', $affiliate->id)
            ->where('payout_status', Order::STATUS_UNPAID)
            ->get();

        foreach ($unpaidOrders as $order) {

            // Dispatch the PayoutOrderJob
            Queue::push(new PayoutOrderJob($order));
        }
    }

    public function getOrderStats(array $data): array
    {
        // Get the 'from' and 'to' dates from the request
        $fromDate = $data['from'];
        $toDate = $data['to'];

        // Get the count of total orders in the date range
        $orderCount = $this->order->whereBetween('created_at', [$fromDate, $toDate])
            ->count();

        // Get the total amount of unpaid commissions for orders with an affiliate
        $unpaidCommissions = $this->order->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('commission_owed');

        // Get the sum of order subtotals
        $totalRevenue = $this->order->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('subtotal');

        // Get the sum of commission owed for orders without an affiliate
        $noAffiliateCommissions = $this->order->whereNull('affiliate_id')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('commission_owed');

        // Calculate the final commissions owed
        $commissionsOwed = $unpaidCommissions - $noAffiliateCommissions;

        return  [
            'count' => $orderCount,
            'commissions_owed' => $commissionsOwed,
            'revenue' => $totalRevenue,
        ];
    }
}
