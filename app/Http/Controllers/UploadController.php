<?php

namespace App\Http\Controllers;

use App\Services\ReceiptUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function __construct(
        protected ReceiptUploadService $uploadService,
    ) {}

    public function index(): View
    {
        return view('upload.index');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $wantsJson = $request->wantsJson();

        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'pdf.mimes' => 'Only PDF files are accepted',
        ]);

        $result = $this->uploadService->processUpload($request->file('pdf'));

        if ($wantsJson) {
            $statusCode = match ($result['status']) {
                'failed' => isset($result['errors']) && count($result['errors']) > 0 ? 422 : 500,
                default => 200,
            };
            return response()->json($result, $statusCode);
        }

        return $this->redirectWithFlash($result);
    }

    /**
     * Redirect to the appropriate page with a flash message based on result status.
     */
    protected function redirectWithFlash(array $result): RedirectResponse
    {
        return match ($result['status']) {
            'failed' => back()->with('error', $result['message'] ?? 'Failed to import receipt'),
            'duplicate' => back()->with('warning', $result['message']),
            'partial' => redirect()->route('receipts.show', $result['receipt_id'])
                ->with('warning', "{$result['item_count']} items imported, some items could not be parsed"),
            'success' => $result['unmatched_bonus_count'] > 0
                ? redirect()->route('receipts.show', $result['receipt_id'])->with('warning', $result['message'])
                : redirect()->route('receipts.show', $result['receipt_id'])->with('success', $result['message']),
            default => back()->with('error', 'Unknown error occurred'),
        };
    }
}
