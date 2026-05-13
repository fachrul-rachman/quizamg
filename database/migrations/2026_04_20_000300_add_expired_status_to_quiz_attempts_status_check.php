<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const array ATTEMPT_STATUSES = [
        'not_started',
        'in_progress',
        'submitted',
        'auto_submitted',
        'expired',
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE quiz_attempts DROP CONSTRAINT IF EXISTS quiz_attempts_status_check');
            DB::statement(sprintf(
                "ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_status_check CHECK (status IN (%s))",
                implode(', ', array_map(fn ($v) => DB::getPdo()->quote($v), self::ATTEMPT_STATUSES)),
            ));
        }

        if ($driver === 'mysql') {
            DB::statement(sprintf(
                "ALTER TABLE quiz_attempts MODIFY status ENUM(%s) NOT NULL",
                implode(', ', array_map(fn ($v) => DB::getPdo()->quote($v), self::ATTEMPT_STATUSES)),
            ));
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        $statuses = array_values(array_filter(self::ATTEMPT_STATUSES, fn (string $v) => $v !== 'expired'));

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE quiz_attempts DROP CONSTRAINT IF EXISTS quiz_attempts_status_check');
            DB::statement(sprintf(
                "ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_status_check CHECK (status IN (%s))",
                implode(', ', array_map(fn ($v) => DB::getPdo()->quote($v), $statuses)),
            ));
        }

        if ($driver === 'mysql') {
            DB::statement(sprintf(
                "ALTER TABLE quiz_attempts MODIFY status ENUM(%s) NOT NULL",
                implode(', ', array_map(fn ($v) => DB::getPdo()->quote($v), $statuses)),
            ));
        }
    }
};

