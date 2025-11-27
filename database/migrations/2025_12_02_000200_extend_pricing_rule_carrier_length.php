<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pricing_rules MODIFY carrier VARCHAR(5) NULL');
        DB::statement('ALTER TABLE pricing_rules MODIFY plating_carrier VARCHAR(5) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pricing_rules MODIFY carrier VARCHAR(3) NULL');
        DB::statement('ALTER TABLE pricing_rules MODIFY plating_carrier VARCHAR(3) NULL');
    }
};
