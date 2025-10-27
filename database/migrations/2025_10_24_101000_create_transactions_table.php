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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('mode', 20)->default('sandbox');
            $table->string('reference', 191)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status', 30)->default('init');
            $table->longText('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
