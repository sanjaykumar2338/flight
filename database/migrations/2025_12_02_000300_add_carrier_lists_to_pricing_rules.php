<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->json('marketing_carriers')->nullable()->after('marketing_carriers_rule');
            $table->json('operating_carriers')->nullable()->after('operating_carriers_rule');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['marketing_carriers', 'operating_carriers']);
        });
    }
};
