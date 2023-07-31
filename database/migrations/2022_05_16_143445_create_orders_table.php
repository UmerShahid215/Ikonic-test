<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('external_order_id')->nullable();
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('affiliate_id')->nullable()->constrained();
            $table->decimal('subtotal',20,4);
            $table->decimal('commission_owed', 20, 4)->default(0.0);
            $table->string('payout_status')->default(Order::STATUS_UNPAID);

            $table->string('discount_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
