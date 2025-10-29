<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('priority')->default(0);

            $table->string('carrier', 3);
            $table->string('origin', 3)->nullable();
            $table->string('destination', 3)->nullable();
            $table->boolean('both_ways')->default(false);
            $table->enum('travel_type', ['OW', 'RT', 'ANY'])->default('ANY');
            $table->enum('cabin_class', ['ECONOMY', 'BUSINESS', 'PREMIUM', 'FIRST', 'ANY'])->default('ANY');
            $table->string('rbd', 10)->nullable();
            $table->enum('rbd_usage', ['INCLUDE', 'EXCLUDE', 'ANY'])->default('ANY');
            $table->json('passenger_types')->nullable();

            $table->dateTime('sales_since')->nullable();
            $table->dateTime('sales_till')->nullable();
            $table->dateTime('departures_since')->nullable();
            $table->dateTime('departures_till')->nullable();
            $table->dateTime('returns_since')->nullable();
            $table->dateTime('returns_till')->nullable();

            $table->enum('fare_type', ['PUBLIC', 'PRIVATE', 'ANY'])->default('ANY');
            $table->string('promo_code', 32)->nullable();

            $table->enum('kind', ['COMMISSION', 'DISCOUNT', 'FEE']);
            $table->enum('calc_basis', ['BASE_PRICE', 'TOTAL_PRICE'])->default('TOTAL_PRICE');
            $table->decimal('percent', 8, 4)->nullable();
            $table->decimal('flat_amount', 12, 2)->nullable();
            $table->decimal('fee_percent', 8, 4)->nullable();
            $table->decimal('fixed_fee', 12, 2)->nullable();

            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('carrier');
            $table->index('origin');
            $table->index('destination');
            $table->index('priority');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
