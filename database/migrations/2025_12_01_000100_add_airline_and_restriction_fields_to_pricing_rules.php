<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->string('plating_carrier', 3)->nullable()->after('carrier');
            $table->string('marketing_carriers_rule', 32)->nullable()->after('plating_carrier');
            $table->string('operating_carriers_rule', 32)->nullable()->after('marketing_carriers_rule');
            $table->string('flight_restriction_type', 32)->nullable()->after('operating_carriers_rule');
            $table->text('flight_numbers')->nullable()->after('flight_restriction_type');
            $table->boolean('is_primary_pcc')->default(false)->after('fixed_fee');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn([
                'plating_carrier',
                'marketing_carriers_rule',
                'operating_carriers_rule',
                'flight_restriction_type',
                'flight_numbers',
                'is_primary_pcc',
            ]);
        });
    }
};
