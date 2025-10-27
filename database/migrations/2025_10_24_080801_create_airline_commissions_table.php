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
        Schema::create('airline_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('airline_code', 3)->unique();
            $table->string('airline_name')->nullable();
            $table->decimal('markup_percent', 5, 2)->default(0);
            $table->decimal('flat_markup', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airline_commissions');
    }
};
