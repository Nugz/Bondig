<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SQLite doesn't support modifying CHECK constraints, so we need to
     * recreate the table with the updated enum values.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to change enum/check constraints
        Schema::dropIfExists('import_logs_new');

        Schema::create('import_logs_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->string('filename');
            $table->enum('status', ['success', 'partial', 'failed', 'duplicate', 'skipped']);
            $table->integer('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        // Copy data from old table to new table
        DB::statement('INSERT INTO import_logs_new SELECT * FROM import_logs');

        // Drop old table and rename new table
        Schema::drop('import_logs');
        Schema::rename('import_logs_new', 'import_logs');
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: Rolling back this migration will DELETE any import_logs with
     * 'duplicate' or 'skipped' status as these values don't exist in the
     * original schema.
     */
    public function down(): void
    {
        // Check for records that will be lost and warn
        $lostRecords = DB::table('import_logs')
            ->whereIn('status', ['duplicate', 'skipped'])
            ->count();

        if ($lostRecords > 0) {
            Log::warning("Migration rollback will delete {$lostRecords} import_logs with duplicate/skipped status");
        }

        // Recreate original table structure
        Schema::dropIfExists('import_logs_new');

        Schema::create('import_logs_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->string('filename');
            $table->enum('status', ['success', 'partial', 'failed']);
            $table->integer('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        // Copy only compatible records (duplicate/skipped will be lost)
        DB::statement("INSERT INTO import_logs_new SELECT * FROM import_logs WHERE status IN ('success', 'partial', 'failed')");

        Schema::drop('import_logs');
        Schema::rename('import_logs_new', 'import_logs');
    }
};
