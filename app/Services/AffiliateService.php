<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService,
        protected Affiliate $affiliate,
        protected User $user
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        try {
            // Check if the email is already in use as a merchant's email
            if ($merchant->user->email === $email) {
                throw new AffiliateCreateException("Email is already in use as a merchant.");
            }

            // Check if the email is already in use as an affiliate's email
            if ($this->affiliate->whereHas('user', function ($query) use ($email) {
                $query->where('email', $email);
            })->exists()) {
                throw new AffiliateCreateException("Email is already in use as an affiliate.");
            }

            // Create a new user
            $user = $this->user->create([
                'name' => $name,
                'email' => $email,
                'type' => User::TYPE_AFFILIATE,
            ]);

            // Create a new affiliate with the provided data
            $affiliate = $this->affiliate->create([
                'merchant_id' => $merchant->id,
                'user_id' => $user->id,
                'commission_rate' => $commissionRate,
                'discount_code' => $this->apiService->createDiscountCode($merchant)['code'],
            ]);

            // Email the newly registered affiliate
            Mail::to($email)->send(new AffiliateCreated($affiliate));

            return $affiliate;
        }catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * @return ApiService
     */
}
