<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\LineItem;
use App\Models\Receipt;
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
            $receipt = DB::transaction(function () use ($result, $file, $filename) {
                $pdfPath = $file->store('receipts', 'local');

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

                $status = $errorCount === 0 ? 'success' : ($errorCount < count($result->lines) ? 'partial' : 'failed');

                ImportLog::create([
                    'receipt_id' => $receipt->id,
                    'filename' => $filename,
                    'status' => $status,
                    'error_count' => $errorCount,
                    'errors' => $errors,
                ]);

                return $receipt;
            });

            $itemCount = $receipt->lineItems()->count();
            $total = number_format($receipt->total_amount, 2);

            if ($receipt->importLogs()->where('status', 'partial')->exists()) {
                return redirect()->route('receipts.show', $receipt)
                    ->with('warning', "{$itemCount} items imported, some items could not be parsed");
            }

            return redirect()->route('receipts.show', $receipt)
                ->with('success', "Receipt imported successfully: {$itemCount} items, \u{20AC}{$total}");

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
}
