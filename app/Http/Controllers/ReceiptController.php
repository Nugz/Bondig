<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function index(): View
    {
        $receipts = Receipt::withCount('lineItems')
            ->latest('purchased_at')
            ->paginate(20);

        return view('receipts.index', compact('receipts'));
    }

    public function show(Receipt $receipt): View
    {
        $receipt->loadCount('lineItems');
        $receipt->load('lineItems.product');

        $unmatchedBonusCount = $receipt->pendingUnmatchedBonuses()->count();
        $totalDiscount = $receipt->total_discount;

        return view('receipts.show', compact('receipt', 'unmatchedBonusCount', 'totalDiscount'));
    }
}
