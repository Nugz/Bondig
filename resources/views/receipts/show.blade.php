<x-layouts.app>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-base-content">Receipt Details</h1>
                <p class="text-base-content/60 mt-1">{{ $receipt->store }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('receipts.index') }}" class="btn btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Receipts
                </a>
                <a href="{{ route('upload') }}" class="btn btn-outline btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload Another
                </a>
            </div>
        </div>

        <!-- Unmatched Bonuses Alert -->
        @if($unmatchedBonusCount > 0)
            <div class="alert alert-warning mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ $unmatchedBonusCount }} unmatched bonus{{ $unmatchedBonusCount > 1 ? 'es' : '' }} need your attention</span>
                <a href="{{ route('receipts.match-bonuses', $receipt) }}" class="btn btn-sm btn-warning">
                    Match Now
                </a>
            </div>
        @endif

        <!-- Receipt Summary Card -->
        <div class="card bg-base-100 shadow-md mb-6">
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-base-content/60">Date & Time</p>
                        <p class="text-lg font-semibold">{{ $receipt->purchased_at->format('d M Y') }}</p>
                        <p class="text-base-content/60">{{ $receipt->purchased_at->format('H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-base-content/60">Items</p>
                        <p class="text-lg font-semibold">{{ $receipt->line_items_count }} {{ $receipt->line_items_count === 1 ? 'product' : 'products' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-base-content/60">Total Amount</p>
                        <p class="text-2xl font-bold text-primary">&euro;{{ number_format($receipt->total_amount, 2, ',', '.') }}</p>
                    </div>
                    @if($totalDiscount > 0)
                        <div>
                            <p class="text-sm text-base-content/60">Total Savings</p>
                            <p class="text-2xl font-bold text-success">-&euro;{{ number_format($totalDiscount, 2, ',', '.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="card bg-base-100 shadow-md mb-6">
            <div class="card-body">
                <h2 class="card-title mb-4">Products</h2>

                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Discount</th>
                                <th class="text-right">Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipt->lineItems as $index => $item)
                                <tr x-data="{ showRawText: false }">
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span>{{ $item->product->name }}</span>
                                            @if($item->is_bonus)
                                                <span class="badge badge-success badge-sm">BONUS</span>
                                            @endif
                                            @if($item->raw_text)
                                                <button
                                                    @click="showRawText = !showRawText"
                                                    class="btn btn-ghost btn-xs text-base-content/50 hover:text-base-content"
                                                    title="View original text"
                                                    aria-label="Toggle original text view"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                        <div x-show="showRawText" x-collapse class="mt-2">
                                            <code class="text-xs bg-base-200 px-2 py-1 rounded block max-h-24 overflow-y-auto">{{ $item->raw_text }}</code>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-right">&euro;{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                    <td class="text-right">&euro;{{ number_format($item->total_price, 2, ',', '.') }}</td>
                                    <td class="text-right">
                                        @if($item->discount_amount)
                                            <span class="text-success font-medium">-&euro;{{ number_format($item->discount_amount, 2, ',', '.') }}</span>
                                        @else
                                            <span class="text-base-content/40">-</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-medium">
                                        @if($item->discount_amount)
                                            <span class="text-primary">&euro;{{ number_format($item->effective_price, 2, ',', '.') }}</span>
                                        @else
                                            &euro;{{ number_format($item->total_price, 2, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-bold">
                                <td colspan="2"></td>
                                <td class="text-right">Subtotal</td>
                                <td class="text-right">&euro;{{ number_format($receipt->lineItems->sum('total_price'), 2, ',', '.') }}</td>
                                <td class="text-right text-success">
                                    @if($totalDiscount > 0)
                                        -&euro;{{ number_format($totalDiscount, 2, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-right text-primary">&euro;{{ number_format($receipt->total_amount, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Raw Text Toggle -->
        <div class="card bg-base-100 shadow-md" x-data="{ showRawText: false }">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Raw Receipt Text</h2>
                    <button
                        @click="showRawText = !showRawText"
                        class="btn btn-sm btn-ghost"
                    >
                        <span x-text="showRawText ? 'Hide' : 'Show'"></span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 ml-1 transition-transform"
                            :class="{ 'rotate-180': showRawText }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div x-show="showRawText" x-collapse>
                    <pre class="mt-4 p-4 bg-base-200 rounded-lg text-sm overflow-x-auto whitespace-pre-wrap">{{ $receipt->raw_text ?? 'No raw text available' }}</pre>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
