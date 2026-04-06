<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // PostgreSQL-specific: extend codigo column from varchar(8) to varchar(20).
        // SQLite (used in tests) stores all TEXT without length limits, so skip.
        if (\DB::getDriverName() === 'pgsql') {
            \DB::statement('ALTER TABLE affiliates ALTER COLUMN codigo TYPE varchar(20)');
        }
    }

    public function down(): void
    {
        if (\DB::getDriverName() === 'pgsql') {
            \DB::statement('ALTER TABLE affiliates ALTER COLUMN codigo TYPE varchar(8)');
        }
    }
};
