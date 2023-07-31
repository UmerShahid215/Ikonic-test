<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService,
        protected ApiService $apiService,
        protected Merchant $merchant,
        protected Order $order,
        protected Affiliate $affiliate
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data): void
    {
        try {
            // Check if the order with the given order_id already exists
            $existingOrder = $this->order->where('external_order_id', $data['order_id'])->first();

            // If the order already exists, ignore the duplicate
            if ($existingOrder) {
                return;
            }

            // Find or create the Merchant based on the merchant domain
            $merchant = $this->merchant->where(['domain' => $data['merchant_domain']])->first();

            // Check if the email is already in use as an affiliate's email
            $affiliateCheck =  $this->affiliate->whereHas('user', function ($query) use ($data) {
                $query->where('email', $data['customer_email']);
            })->first();

            if (!$affiliateCheck) {
                $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 0.1);
            }

            $affiliate = $this->affiliate->where('discount_code', $data['discount_code'])->first();
            // Calculate the commission owed for the order based on the affiliate's commission rate from the database
            $commissionOwed = $data['subtotal_price'] * $affiliate->commission_rate ?? 0.1;

            // Create a new order and associate it with the Merchant and Affiliate
            $order = new Order([
                'external_order_id' => $data['order_id'],
                'subtotal' => $data['subtotal_price'],
                'commission_owed' => $commissionOwed,
            ]);

            $merchant->orders()->save($order);
            $affiliate->orders()->save($order);
        }catch (\Exception $e){
            throw $e;
        }
    }
}
