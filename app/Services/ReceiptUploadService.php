<?php

namespace App\Services;

use App\DTOs\ParseResult;
use App\Models\ImportLog;
use App\Models\LineItem;
use App\Models\Receipt;
use App\Models\UnmatchedBonus;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReceiptUploadService
{
    /**
     * The store name for receipt processing.
     * Currently only Albert Heijn is supported, but this constant
     * makes it easy to extend for other stores in the future.
     */
    protected const DEFAULT_STORE = 'Albert Heijn';

    public function __construct(
        protected ReceiptParsingService $parsingService,
        protected ProductMatchingService $productService,
        protected BonusMatchingService $bonusMatchingService,
    ) {}

    /**
     * Process a single PDF upload and return the result.
     *
     * @return array{status: string, filename: string, receipt_id?: int, item_count?: int, total_amount?: float, unmatched_bonus_count?: int, message?: string, errors?: array}
     */
    public function processUpload(UploadedFile $file): array
    {
        $filename = $file->getClientOriginalName();

        try {
            $result = $this->parsingService->parseFromPdf($file);
        } catch (\Exception $e) {
            Log::error('Receipt parsing threw exception', [
                'file' => $filename,
                'error' => $e->getMessage(),
            ]);

            ImportLog::create([
                'receipt_id' => null,
                'filename' => $filename,
                'status' => 'failed',
                'error_count' => 1,
                'errors' => [$e->getMessage()],
            ]);

            return [
                'status' => 'failed',
                'filename' => $filename,
                'errors' => [$e->getMessage()],
                'message' => 'Failed to parse receipt: ' . $e->getMessage(),
            ];
        }

        if (!$result->success) {
            return $this->handleParseFailure($filename, $result);
        }

        if ($this->isDuplicate($result)) {
            return $this->handleDuplicate($filename);
        }

        return $this->processReceipt($file, $filename, $result);
    }

    /**
     * Handle a parse failure by logging it and returning error response.
     */
    protected function handleParseFailure(string $filename, ParseResult $result): array
    {
        ImportLog::create([
            'receipt_id' => null,
            'filename' => $filename,
            'status' => 'failed',
            'error_count' => count($result->errors),
            'errors' => $result->errors,
        ]);

        Log::warning('Receipt parsing failed', [
            'file' => $filename,
            'errors' => $result->errors,
        ]);

        return [
            'status' => 'failed',
            'filename' => $filename,
            'errors' => $result->errors,
            'message' => 'Failed to parse receipt: ' . implode(', ', $result->errors),
        ];
    }

    /**
     * Check if the receipt is a duplicate.
     *
     * @param ParseResult $result The parsed receipt result
     * @param string|null $store The store name to check (defaults to DEFAULT_STORE)
     */
    protected function isDuplicate(ParseResult $result, ?string $store = null): bool
    {
        // Only check for duplicates if we have a valid date to compare
        if ($result->purchasedAt === null) {
            return false;
        }

        $store = $store ?? self::DEFAULT_STORE;

        // Use whereDate to properly compare dates in SQLite (which stores dates as datetime strings)
        return Receipt::where('store', $store)
            ->whereDate('purchased_date', $result->purchasedAt->format('Y-m-d'))
            ->where('total_amount', $result->total ?? 0)
            ->exists();
    }

    /**
     * Handle a duplicate receipt by logging it and returning warning response.
     */
    protected function handleDuplicate(string $filename): array
    {
        ImportLog::create([
            'receipt_id' => null,
            'filename' => $filename,
            'status' => 'duplicate',
            'error_count' => 0,
            'errors' => ['Duplicate receipt detected'],
        ]);

        return [
            'status' => 'duplicate',
            'filename' => $filename,
            'message' => 'Duplicate receipt detected - already imported',
        ];
    }

    /**
     * Process the parsed receipt and create database records.
     */
    protected function processReceipt(UploadedFile $file, string $filename, ParseResult $result): array
    {
        // Store file BEFORE transaction to prevent orphan files on rollback
        $pdfPath = $file->store('receipts', 'local');

        try {
            $transactionResult = DB::transaction(function () use ($pdfPath, $filename, $result) {
                $receipt = Receipt::create([
                    'store' => self::DEFAULT_STORE,
                    'purchased_at' => $result->purchasedAt ?? now(),
                    'total_amount' => $result->total ?? 0,
                    'pdf_path' => $pdfPath,
                    'raw_text' => $result->rawText,
                ]);

                $errorCount = 0;
                $errors = [];

                foreach ($result->lines as $parsedLine) {
                    try {
                        $product = $this->productService->findOrCreate($parsedLine->name);

                        LineItem::create([
                            'receipt_id' => $receipt->id,
                            'product_id' => $product->id,
                            'quantity' => $parsedLine->quantity,
                            'unit_price' => $parsedLine->unitPrice,
                            'total_price' => $parsedLine->totalPrice,
                            'is_bonus' => $parsedLine->isBonus,
                            'raw_text' => $parsedLine->rawText,
                        ]);
                    } catch (\Exception $e) {
                        $errorCount++;
                        $errors[] = "Failed to process line: {$parsedLine->name}";
                        Log::warning('Failed to create line item', [
                            'receipt_id' => $receipt->id,
                            'line' => $parsedLine->name,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $unmatchedCount = $this->processBonuses($receipt, $result->bonuses);
                $status = $errorCount === 0 ? 'success' : ($errorCount < count($result->lines) ? 'partial' : 'failed');

                ImportLog::create([
                    'receipt_id' => $receipt->id,
                    'filename' => $filename,
                    'status' => $status,
                    'error_count' => $errorCount,
                    'errors' => $errors,
                ]);

                return [
                    'receipt' => $receipt,
                    'unmatchedCount' => $unmatchedCount,
                    'status' => $status,
                    'errors' => $errors,
                ];
            });

            $receipt = $transactionResult['receipt'];
            $unmatchedCount = $transactionResult['unmatchedCount'];
            $status = $transactionResult['status'];
            $errors = $transactionResult['errors'];
            $itemCount = $receipt->lineItems()->count();
            $total = number_format($receipt->total_amount, 2);

            $message = match ($status) {
                'failed' => "Failed to import receipt: all line items failed",
                'partial' => "Partially imported: {$itemCount} items, some lines failed",
                default => "Receipt imported successfully: {$itemCount} items, €{$total}",
            };
            if ($unmatchedCount > 0 && $status !== 'failed') {
                $message .= " ({$unmatchedCount} unmatched bonuses)";
            }

            $result = [
                'status' => $status,
                'filename' => $filename,
                'receipt_id' => $receipt->id,
                'item_count' => $itemCount,
                'total_amount' => $receipt->total_amount,
                'unmatched_bonus_count' => $unmatchedCount,
                'message' => $message,
            ];

            // Include errors array for failed/partial status to ensure proper HTTP status code
            if (!empty($errors)) {
                $result['errors'] = $errors;
            }

            return $result;
        } catch (UniqueConstraintViolationException $e) {
            // Database-level duplicate detection (constraint violation)
            Storage::disk('local')->delete($pdfPath);

            Log::info('Duplicate receipt detected by database constraint', [
                'file' => $filename,
            ]);

            return $this->handleDuplicate($filename);
        } catch (\Exception $e) {
            // Clean up stored file since transaction failed
            Storage::disk('local')->delete($pdfPath);

            Log::error('Receipt import failed', [
                'file' => $filename,
                'error' => $e->getMessage(),
            ]);

            ImportLog::create([
                'receipt_id' => null,
                'filename' => $filename,
                'status' => 'failed',
                'error_count' => 1,
                'errors' => [$e->getMessage()],
            ]);

            return [
                'status' => 'failed',
                'filename' => $filename,
                'errors' => [$e->getMessage()],
                'message' => 'Failed to import receipt: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process bonus lines from the parsed receipt.
     *
     * @param Receipt $receipt
     * @param array $bonuses ParsedBonus[]
     * @return int Number of unmatched bonuses
     */
    protected function processBonuses(Receipt $receipt, array $bonuses): int
    {
        if (empty($bonuses)) {
            return 0;
        }

        $lineItems = $receipt->lineItems()->with('product')->get();
        $unmatchedCount = 0;

        foreach ($bonuses as $bonus) {
            $matchResult = $this->bonusMatchingService->matchBonusToProduct($bonus, $lineItems);

            if ($matchResult !== null) {
                $matchResult->lineItem->update([
                    'discount_amount' => $bonus->discountAmount,
                ]);

                Log::info('Bonus matched', [
                    'receipt_id' => $receipt->id,
                    'bonus_name' => $bonus->rawName,
                    'matched_product' => $matchResult->lineItem->product->name,
                    'confidence' => $matchResult->confidence,
                    'discount' => $bonus->discountAmount,
                ]);
            } else {
                UnmatchedBonus::create([
                    'receipt_id' => $receipt->id,
                    'raw_name' => $bonus->rawName,
                    'discount_amount' => $bonus->discountAmount,
                    'status' => 'pending',
                ]);

                $unmatchedCount++;

                Log::info('Bonus unmatched', [
                    'receipt_id' => $receipt->id,
                    'bonus_name' => $bonus->rawName,
                    'discount' => $bonus->discountAmount,
                ]);
            }
        }

        return $unmatchedCount;
    }
}
