<div class="container mx-auto px-4 pt-4" x-data="{ showSuccess: true, showWarning: true, showError: true }">
    @if (session('success'))
        <div
            x-show="showSuccess"
            x-init="setTimeout(() => showSuccess = false, 4000)"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            role="alert"
            class="alert alert-success mb-4"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('warning'))
        <div
            x-show="showWarning"
            role="alert"
            class="alert alert-warning mb-4"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('warning') }}</span>
            <button @click="showWarning = false" class="btn btn-sm btn-ghost">Dismiss</button>
        </div>
    @endif

    @if (session('error'))
        <div
            x-show="showError"
            role="alert"
            class="alert alert-error mb-4"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
            <button @click="showError = false" class="btn btn-sm btn-ghost">Dismiss</button>
        </div>
    @endif
</div>
