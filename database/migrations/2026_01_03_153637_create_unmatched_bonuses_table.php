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
        Schema::create('unmatched_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->string('raw_name');
            $table->decimal('discount_amount', 8, 2);
            $table->foreignId('matched_line_item_id')->nullable()->constrained('line_items')->nullOnDelete();
            $table->enum('status', ['pending', 'matched', 'not_applicable'])->default('pending');
            $table->timestamps();

            // Index for filtering unmatched bonuses by receipt
            $table->index(['receipt_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unmatched_bonuses');
    }
};
