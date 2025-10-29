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
        Schema::table('pricing_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_rules', 'usage')) {
                $table->string('usage', 64)->default('commission_base')->after('carrier');
            }
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_rules', 'travel_type')) {
                $table->dropColumn('travel_type');
            }
            if (Schema::hasColumn('pricing_rules', 'cabin_class')) {
                $table->dropColumn('cabin_class');
            }
            if (Schema::hasColumn('pricing_rules', 'rbd')) {
                $table->dropColumn('rbd');
            }
            if (Schema::hasColumn('pricing_rules', 'rbd_usage')) {
                $table->dropColumn('rbd_usage');
            }
            if (Schema::hasColumn('pricing_rules', 'fare_type')) {
                $table->dropColumn('fare_type');
            }
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->string('travel_type', 8)->nullable()->after('both_ways');
            $table->string('cabin_class', 32)->nullable()->after('travel_type');
            $table->string('booking_class_rbd', 20)->nullable()->after('cabin_class');
            $table->string('booking_class_usage', 32)->nullable()->after('booking_class_rbd');
            $table->string('fare_type', 32)->nullable()->after('promo_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn([
                'usage',
                'travel_type',
                'cabin_class',
                'booking_class_rbd',
                'booking_class_usage',
                'fare_type',
            ]);

            $table->enum('travel_type', ['OW', 'RT', 'ANY'])->default('ANY')->after('both_ways');
            $table->enum('cabin_class', ['ECONOMY', 'BUSINESS', 'PREMIUM', 'FIRST', 'ANY'])->default('ANY')->after('travel_type');
            $table->string('rbd', 10)->nullable()->after('cabin_class');
            $table->enum('rbd_usage', ['INCLUDE', 'EXCLUDE', 'ANY'])->default('ANY')->after('rbd');
            $table->enum('fare_type', ['PUBLIC', 'PRIVATE', 'ANY'])->default('ANY')->after('promo_code');
        });
    }
};
