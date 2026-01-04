<x-layouts.app>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-base-content">Receipts</h1>
                <p class="text-base-content/60 mt-1">Your uploaded shopping receipts</p>
            </div>
            <a href="{{ route('upload') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Upload Receipt
            </a>
        </div>

        <!-- Receipts List -->
        @forelse($receipts as $receipt)
            <a href="{{ route('receipts.show', $receipt) }}" class="block mb-4">
                <div class="card bg-base-100 shadow hover:shadow-md transition-shadow">
                    <div class="card-body py-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="card-title text-lg">{{ $receipt->store }}</h3>
                                <p class="text-sm text-base-content/70">
                                    {{ $receipt->purchased_at->format('d M Y') }} &middot; {{ $receipt->purchased_at->format('H:i') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold">&euro;{{ number_format($receipt->total_amount, 2, ',', '.') }}</p>
                                <p class="text-sm text-base-content/70">
                                    {{ $receipt->line_items_count }} {{ $receipt->line_items_count === 1 ? 'item' : 'items' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium">No receipts yet</h3>
                <p class="mt-1 text-sm text-base-content/60">
                    Get started by uploading your first receipt.
                </p>
                <div class="mt-6">
                    <a href="{{ route('upload') }}" class="btn btn-primary">
                        Upload Receipt
                    </a>
                </div>
            </div>
        @endforelse

        <!-- Pagination -->
        @if($receipts->hasPages())
            <div class="mt-6">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
