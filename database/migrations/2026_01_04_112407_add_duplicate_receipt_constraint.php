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
     * SQLite doesn't support functional indexes, so we add a purchased_date column
     * derived from purchased_at for the unique constraint.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->date('purchased_date')->nullable()->after('purchased_at');
        });

        // Populate purchased_date from existing purchased_at values
        DB::statement("UPDATE receipts SET purchased_date = DATE(purchased_at)");

        // Remove existing duplicates before adding constraint, keeping only the first (lowest id)
        // First, find duplicates and their line_items/logs, then delete
        $duplicates = DB::select("
            SELECT r.id, r.store, r.purchased_date, r.total_amount, r.created_at
            FROM receipts r
            WHERE r.id NOT IN (
                SELECT MIN(id)
                FROM receipts
                GROUP BY store, purchased_date, total_amount
            )
        ");

        if (!empty($duplicates)) {
            $duplicateIds = array_column($duplicates, 'id');

            // Log warning about duplicate data being deleted for audit trail
            Log::warning('Migration: Removing duplicate receipts to add unique constraint', [
                'duplicate_count' => count($duplicates),
                'duplicate_ids' => $duplicateIds,
                'duplicates' => array_map(fn($d) => [
                    'id' => $d->id,
                    'store' => $d->store,
                    'purchased_date' => $d->purchased_date,
                    'total_amount' => $d->total_amount,
                    'created_at' => $d->created_at,
                ], $duplicates),
            ]);

            // Count related records that will be deleted
            $lineItemCount = DB::table('line_items')->whereIn('receipt_id', $duplicateIds)->count();
            $importLogCount = DB::table('import_logs')->whereIn('receipt_id', $duplicateIds)->count();
            $unmatchedBonusCount = DB::table('unmatched_bonuses')->whereIn('receipt_id', $duplicateIds)->count();

            Log::warning('Migration: Deleting related records for duplicate receipts', [
                'line_items_deleted' => $lineItemCount,
                'import_logs_deleted' => $importLogCount,
                'unmatched_bonuses_deleted' => $unmatchedBonusCount,
            ]);

            // Delete related records first (foreign key constraints)
            DB::table('line_items')->whereIn('receipt_id', $duplicateIds)->delete();
            DB::table('import_logs')->whereIn('receipt_id', $duplicateIds)->delete();
            DB::table('unmatched_bonuses')->whereIn('receipt_id', $duplicateIds)->delete();

            // Delete the duplicate receipts
            DB::table('receipts')->whereIn('id', $duplicateIds)->delete();

            Log::info('Migration: Successfully removed duplicate receipts', [
                'receipts_deleted' => count($duplicateIds),
            ]);
        }

        // Add unique constraint for duplicate detection
        Schema::table('receipts', function (Blueprint $table) {
            $table->unique(['store', 'purchased_date', 'total_amount'], 'receipts_duplicate_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique('receipts_duplicate_check');
            $table->dropColumn('purchased_date');
        });
    }
};
