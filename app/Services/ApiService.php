<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * You don't need to do anything here. This is just to help
 */
class ApiService
{
    /**
     * Create a new discount code for an affiliate
     *
     * @param Merchant $merchant
     *
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => rand(0, 100000),
            'code' => Str::uuid()
        ];
    }

    /**
     * Send a payout to an email
     *
     * @param  string $email
     * @param  float $amount
     * @return void
     * @throws RuntimeException
     */
    public function sendPayout(string $email, float $amount)
    {
        try {


            if ($amount > 0) {
                // Simulate a successful payout
                Log::info("Payout successful to email: {$email}, amount: {$amount}");

            } else {
                // Simulate a failed payout
                Log::error("Payout failed to email: $email, amount: $amount");
                throw new RuntimeException('Payout failed. Please try again later.');
            }
        }catch (\Exception $exception){
            throw new RuntimeException('Payout failed. Please try again later.');
        }
    }
}
