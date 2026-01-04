<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\UnmatchedBonus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BonusMatchingController extends Controller
{
    public function index(Receipt $receipt): View
    {
        $unmatchedBonuses = $receipt->unmatchedBonuses()
            ->where('status', 'pending')
            ->get();

        $lineItems = $receipt->lineItems()
            ->with('product')
            ->get();

        return view('receipts.match-bonuses', [
            'receipt' => $receipt,
            'unmatchedBonuses' => $unmatchedBonuses,
            'lineItems' => $lineItems,
        ]);
    }

    public function match(Request $request, Receipt $receipt, UnmatchedBonus $bonus): JsonResponse
    {
        // Validate the bonus belongs to this receipt
        if ($bonus->receipt_id !== $receipt->id) {
            return response()->json(['error' => __('validation.bonus_not_found')], 403);
        }

        $request->validate([
            'line_item_id' => ['nullable', 'exists:line_items,id'],
            'not_applicable' => ['boolean'],
        ]);

        if ($request->boolean('not_applicable')) {
            // Mark as not applicable (store-wide discount)
            $bonus->update([
                'status' => 'not_applicable',
                'matched_line_item_id' => null,
            ]);

            Log::info('Bonus marked as not applicable', [
                'receipt_id' => $receipt->id,
                'bonus_id' => $bonus->id,
                'bonus_name' => $bonus->raw_name,
                'discount_amount' => $bonus->discount_amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bonus marked as not applicable',
            ]);
        }

        if ($request->has('line_item_id')) {
            $lineItemId = $request->input('line_item_id');

            // Verify line item belongs to this receipt
            $lineItem = $receipt->lineItems()->find($lineItemId);
            if (!$lineItem) {
                return response()->json(['error' => __('validation.line_item_not_found')], 403);
            }

            // Prevent overwriting existing discount unless explicitly confirmed
            if ($lineItem->discount_amount !== null && $lineItem->discount_amount > 0) {
                return response()->json([
                    'error' => __('validation.discount_already_exists'),
                    'existing_discount' => $lineItem->discount_amount,
                ], 409);
            }

            // Update line item's discount amount
            $lineItem->update([
                'discount_amount' => $bonus->discount_amount,
            ]);

            // Mark bonus as matched
            $bonus->update([
                'status' => 'matched',
                'matched_line_item_id' => $lineItemId,
            ]);

            Log::info('Bonus manually matched to product', [
                'receipt_id' => $receipt->id,
                'bonus_id' => $bonus->id,
                'bonus_name' => $bonus->raw_name,
                'matched_line_item_id' => $lineItemId,
                'matched_product' => $lineItem->product->name,
                'discount_amount' => $bonus->discount_amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bonus matched successfully',
                'product_name' => $lineItem->product->name,
            ]);
        }

        return response()->json(['error' => __('validation.invalid_request')], 400);
    }
}
