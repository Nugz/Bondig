<x-layouts.app>
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-base-content mb-6">Upload Receipt</h1>

        <div class="card bg-base-100 shadow-md">
            <div class="card-body">
                <p class="text-base-content/80 mb-4">
                    Upload your grocery receipt PDF to automatically extract and track your purchases.
                </p>

                <x-upload-dropzone />
            </div>
        </div>
    </div>
</x-layouts.app>
