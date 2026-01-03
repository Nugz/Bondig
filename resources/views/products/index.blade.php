<x-layouts.app>
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-base-content mb-6">Products</h1>

        <div class="card bg-base-100 shadow-md">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="card-title text-primary">All Products</h2>
                    <div class="form-control">
                        <input type="text" placeholder="Search products..." class="input input-bordered w-full max-w-xs" disabled />
                    </div>
                </div>

                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-base-content/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="text-base-content/60 text-lg">No products yet</p>
                    <p class="text-base-content/40 mt-2">Upload a receipt to start tracking your products.</p>
                    <a href="{{ route('upload') }}" class="btn btn-primary mt-4">Upload Receipt</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
