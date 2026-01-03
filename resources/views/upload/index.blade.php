<x-layouts.app>
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-base-content mb-6">Upload Receipt</h1>

        <div class="card bg-base-100 shadow-md">
            <div class="card-body">
                <p class="text-base-content/80 mb-4">
                    Upload your grocery receipt PDF to automatically extract and track your purchases.
                </p>

                <!-- Placeholder Upload Area -->
                <div class="border-2 border-dashed border-base-300 rounded-lg p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/40 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p class="text-base-content/60">
                        Drag and drop your PDF here, or click to browse
                    </p>
                    <p class="text-sm text-base-content/40 mt-2">
                        Supports Albert Heijn receipt PDFs
                    </p>
                </div>

                <div class="card-actions justify-end mt-4">
                    <button class="btn btn-primary" disabled>Upload</button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
