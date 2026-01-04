<x-layouts.app>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-base-content">Match Bonuses</h1>
                <p class="text-base-content/60 mt-1">{{ $receipt->store }} - {{ $receipt->purchased_at->format('d M Y') }}</p>
            </div>
            <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Receipt
            </a>
        </div>

        @if($unmatchedBonuses->isEmpty())
            <div class="card bg-base-100 shadow-md">
                <div class="card-body text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-success mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-xl font-semibold">All bonuses matched!</h2>
                    <p class="text-base-content/60">There are no unmatched bonuses for this receipt.</p>
                    <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-primary mt-4">View Receipt</a>
                </div>
            </div>
        @else
            <div class="card bg-base-100 shadow-md mb-6">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        {{ $unmatchedBonuses->count() }} Unmatched Bonus{{ $unmatchedBonuses->count() > 1 ? 'es' : '' }}
                    </h2>
                    <p class="text-base-content/60 mb-4">
                        Match each bonus discount to a product on this receipt, or mark it as "Not applicable" if it's a store-wide discount.
                    </p>

                    <div class="space-y-4" x-data="bonusMatching" x-init="receiptId = {{ $receipt->id }}; totalBonuses = {{ $unmatchedBonuses->count() }}; redirectUrl = '{{ route('receipts.show', $receipt) }}'">
                        @foreach($unmatchedBonuses as $bonus)
                            <div
                                class="border border-base-300 rounded-lg p-4 transition-all"
                                :class="{ 'opacity-50 bg-success/10': matchedBonuses.includes({{ $bonus->id }}) }"
                                id="bonus-{{ $bonus->id }}"
                            >
                                <div class="flex flex-col md:flex-row md:items-center gap-4">
                                    <!-- Bonus Info -->
                                    <div class="flex-1">
                                        <div class="font-mono text-sm bg-base-200 px-2 py-1 rounded inline-block mb-2">
                                            {{ $bonus->raw_name }}
                                        </div>
                                        <div class="text-lg font-bold text-success">
                                            -&euro;{{ number_format($bonus->discount_amount, 2, ',', '.') }}
                                        </div>
                                    </div>

                                    <!-- Matching Controls -->
                                    <div class="flex-1" x-show="!matchedBonuses.includes({{ $bonus->id }})">
                                        <label class="label">
                                            <span class="label-text">Match to product:</span>
                                        </label>
                                        <select
                                            class="select select-bordered w-full"
                                            @change="handleMatch({{ $bonus->id }}, $event.target.value)"
                                        >
                                            <option value="">Select a product...</option>
                                            @foreach($lineItems->where('is_bonus', true) as $item)
                                                <option value="{{ $item->id }}">
                                                    {{ $item->product->name }}
                                                    (&euro;{{ number_format($item->total_price, 2, ',', '.') }})
                                                </option>
                                            @endforeach
                                            <option value="not_applicable">-- Not applicable (store discount) --</option>
                                        </select>
                                    </div>

                                    <!-- Success State -->
                                    <div class="flex-1 text-center" x-show="matchedBonuses.includes({{ $bonus->id }})">
                                        <span class="badge badge-success gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Matched
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- All Products Reference -->
            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h2 class="card-title mb-4">All Products on Receipt</h2>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-center">Bonus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lineItems as $item)
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-right">&euro;{{ number_format($item->total_price, 2, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($item->is_bonus)
                                                <span class="badge badge-success badge-sm">B</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
