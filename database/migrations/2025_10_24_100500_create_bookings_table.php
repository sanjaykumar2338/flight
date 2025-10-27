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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('airline_code', 3)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('customer_email')->nullable();
            $table->string('customer_name')->nullable();
            $table->decimal('amount_base', 12, 2)->default(0);
            $table->decimal('amount_final', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('priced_offer_ref', 191)->nullable();
            $table->string('response_id', 191)->nullable();
            $table->string('primary_carrier', 10)->nullable();
            $table->string('payment_reference', 191)->nullable();
            $table->string('referral_code', 50)->nullable();
            $table->json('passenger_summary')->nullable();
            $table->longText('itinerary_json');
            $table->longText('pricing_json')->nullable();
            $table->timestamps();

            $table->index(['status', 'airline_code']);
            $table->index('referral_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
