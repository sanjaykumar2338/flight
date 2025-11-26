<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Allow global rules by permitting NULL carrier values.
        DB::statement('ALTER TABLE pricing_rules MODIFY carrier VARCHAR(3) NULL');
    }

    public function down(): void
    {
        // Revert to NOT NULL; convert existing NULL carriers to empty string to satisfy constraint.
        DB::statement("UPDATE pricing_rules SET carrier = '' WHERE carrier IS NULL");
        DB::statement('ALTER TABLE pricing_rules MODIFY carrier VARCHAR(3) NOT NULL');
    }
};
