<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\LineItem;
use App\Models\Receipt;
use App\Models\UnmatchedBonus;
use App\Services\BonusMatchingService;
use App\Services\ProductMatchingService;
use App\Services\ReceiptParsingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function __construct(
        protected ReceiptParsingService $parsingService,
        protected ProductMatchingService $productService,
        protected BonusMatchingService $bonusMatchingService,
    ) {}

    public function index(): View
    {
        return view('upload.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'pdf.mimes' => 'Only PDF files are accepted',
        ]);

        $file = $request->file('pdf');
        $filename = $file->getClientOriginalName();

        $result = $this->parsingService->parseFromPdf($file);

        if (!$result->success) {
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

            return back()->with('error', 'Failed to parse receipt: ' . implode(', ', $result->errors));
        }

        try {
            $transactionResult = DB::transaction(function () use ($result, $file, $filename) {
                $pdfPath = $file->store('receipts', 'local');

                // TODO: Extract store from PDF or allow selection (Epic 6, Story 6.5: Manual Receipt Entry)
                $receipt = Receipt::create([
                    'store' => 'Albert Heijn',
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

                // Process bonus matches
                $unmatchedCount = $this->processBonuses($receipt, $result->bonuses);

                $status = $errorCount === 0 ? 'success' : ($errorCount < count($result->lines) ? 'partial' : 'failed');

                ImportLog::create([
                    'receipt_id' => $receipt->id,
                    'filename' => $filename,
                    'status' => $status,
                    'error_count' => $errorCount,
                    'errors' => $errors,
                ]);

                return ['receipt' => $receipt, 'unmatchedCount' => $unmatchedCount];
            });

            $receipt = $transactionResult['receipt'];
            $unmatchedCount = $transactionResult['unmatchedCount'];

            $itemCount = $receipt->lineItems()->count();
            $total = number_format($receipt->total_amount, 2);

            // Build success message with unmatched bonus info
            $message = "Receipt imported successfully: {$itemCount} items, \u{20AC}{$total}";
            if ($unmatchedCount > 0) {
                $message .= " ({$unmatchedCount} unmatched bonuses)";
            }

            if ($receipt->importLogs()->where('status', 'partial')->exists()) {
                return redirect()->route('receipts.show', $receipt)
                    ->with('warning', "{$itemCount} items imported, some items could not be parsed");
            }

            if ($unmatchedCount > 0) {
                return redirect()->route('receipts.show', $receipt)
                    ->with('warning', $message);
            }

            return redirect()->route('receipts.show', $receipt)
                ->with('success', $message);

        } catch (\Exception $e) {
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

            return back()->with('error', 'Failed to import receipt: ' . $e->getMessage());
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
                // Confident match - update the line item's discount amount
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
                // No confident match - create unmatched bonus record
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
