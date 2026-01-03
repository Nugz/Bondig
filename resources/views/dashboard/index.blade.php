<x-layouts.app>
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-base-content mb-6">Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Placeholder Stats Cards -->
            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h2 class="card-title text-primary">Total Spending</h2>
                    <p class="text-3xl font-bold">-</p>
                    <p class="text-sm text-base-content/60">This month</p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h2 class="card-title text-primary">Receipts</h2>
                    <p class="text-3xl font-bold">0</p>
                    <p class="text-sm text-base-content/60">Uploaded</p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h2 class="card-title text-primary">Products</h2>
                    <p class="text-3xl font-bold">0</p>
                    <p class="text-sm text-base-content/60">Tracked</p>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <div class="card bg-base-100 shadow-md">
                <div class="card-body">
                    <h2 class="card-title text-primary mb-4">Getting Started</h2>
                    <p class="text-base-content/80">
                        Welcome to Bondig! Upload your first receipt to start tracking your grocery spending.
                    </p>
                    <div class="card-actions justify-end mt-4">
                        <a href="{{ route('upload') }}" class="btn btn-primary">Upload Receipt</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts.app>
