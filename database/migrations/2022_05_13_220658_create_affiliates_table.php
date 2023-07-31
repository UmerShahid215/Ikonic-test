<?php

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
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('merchant_id');
            /**
             * When dealing with financial calculations, such as calculating commissions or order subtotals,
             * it is essential to maintain exact decimal precision to avoid discrepancies and ensure accurate results.
             * Floats are subject to rounding errors and cannot always represent decimal values with complete precision.
             * These errors can accumulate during complex calculations and lead to incorrect results.
             *
             * To address this issue, the DECIMAL data type is more appropriate for financial data.
             * DECIMAL allows for exact representation of decimal values, ensuring that calculations are precise
             * and free from rounding errors. It stores numbers as strings with a fixed number of digits before and after
             * the decimal point, making it ideal for handling financial amounts, percentages, and other exact decimal values.
             */
            $table->decimal('commission_rate', 20, 4)->default(0.1);
            $table->string('discount_code');
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
        Schema::dropIfExists('affiliates');
    }
};
