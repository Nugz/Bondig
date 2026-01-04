<div
    x-data="{
        isDragging: false,
        isUploading: false,
        files: [],
        fileErrors: [],
        currentIndex: 0,
        results: [],
        csrfToken: '{{ csrf_token() }}',
        uploadUrl: '{{ route('upload.store') }}',
        receiptsUrl: '{{ route('receipts.index') }}',

        handleDrop(e) {
            this.isDragging = false;
            const droppedFiles = Array.from(e.dataTransfer.files);
            this.addFiles(droppedFiles);
        },

        handleFileSelect(e) {
            const selectedFiles = Array.from(e.target.files);
            this.addFiles(selectedFiles);
            // Clear input to allow re-selecting same files
            e.target.value = '';
        },

        addFiles(newFiles) {
            const maxSizeBytes = 10 * 1024 * 1024; // 10MB
            const maxFileCount = 50;

            newFiles.forEach(file => {
                // Check if file already exists
                if (this.files.some(f => f.name === file.name && f.size === file.size)) {
                    return;
                }

                // Check max file count limit
                if (this.files.length >= maxFileCount) {
                    this.fileErrors.push({ name: file.name, error: `Maximum ${maxFileCount} files allowed` });
                    return;
                }

                const error = this.validateFile(file, maxSizeBytes);
                if (error) {
                    this.fileErrors.push({ name: file.name, error: error });
                } else {
                    this.files.push(file);
                }
            });
        },

        validateFile(file, maxSizeBytes) {
            if (file.type !== 'application/pdf') {
                return 'Only PDF files are accepted';
            }
            if (file.size > maxSizeBytes) {
                return 'File size exceeds 10MB limit';
            }
            return null;
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        clearFileError(index) {
            this.fileErrors.splice(index, 1);
        },

        async submitBatch() {
            if (this.files.length === 0 || this.isUploading) return;

            this.isUploading = true;
            this.results = [];
            this.currentIndex = 0;

            for (let i = 0; i < this.files.length; i++) {
                this.currentIndex = i;
                const file = this.files[i];
                const formData = new FormData();
                formData.append('pdf', file);

                try {
                    const response = await fetch(this.uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const result = await response.json();
                    this.results.push({
                        ...result,
                        filename: file.name,
                        httpStatus: response.status
                    });
                } catch (error) {
                    this.results.push({
                        filename: file.name,
                        status: 'failed',
                        message: 'Network error: ' + error.message,
                        httpStatus: 0
                    });
                }
            }

            this.currentIndex = this.files.length;
            this.isUploading = false;
            // Clear files array to free memory - filenames are preserved in results
            this.files = [];
        },

        get successCount() {
            return this.results.filter(r => r.status === 'success' || r.status === 'partial').length;
        },

        get duplicateCount() {
            return this.results.filter(r => r.status === 'duplicate').length;
        },

        get failedCount() {
            return this.results.filter(r => r.status === 'failed').length;
        },

        get isComplete() {
            return this.results.length > 0 && !this.isUploading;
        },

        getStatusIcon(result) {
            if (result.status === 'success' || result.status === 'partial') return 'success';
            if (result.status === 'duplicate') return 'warning';
            return 'error';
        },

        reset() {
            this.files = [];
            this.fileErrors = [];
            this.results = [];
            this.currentIndex = 0;
            this.isUploading = false;
        }
    }"
    class="w-full"
>
    <!-- File Selection / Drop Zone -->
    <template x-if="!isComplete">
        <div>
            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop($event)"
                @click="$refs.fileInput.click()"
                @keydown.enter="$refs.fileInput.click()"
                @keydown.space.prevent="$refs.fileInput.click()"
                :class="{
                    'border-primary bg-primary/5': isDragging,
                    'border-success': files.length > 0 && fileErrors.length === 0,
                    'border-warning': files.length > 0 && fileErrors.length > 0
                }"
                class="border-2 border-dashed border-base-300 rounded-lg p-8 text-center cursor-pointer transition-all duration-200 hover:border-primary/50 hover:bg-base-100"
                style="min-height: 200px;"
                role="button"
                tabindex="0"
                aria-label="Drop zone for PDF receipt files. Click or press Enter to browse files, or drag and drop files here."
            >
                <input
                    type="file"
                    x-ref="fileInput"
                    @change="handleFileSelect($event)"
                    accept="application/pdf"
                    multiple
                    class="hidden"
                    aria-label="Select PDF receipt files to upload"
                >

                <template x-if="!isUploading && files.length === 0">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-primary/60 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-lg text-base-content/80 font-medium">
                            Drag and drop your AH receipts here, or click to browse
                        </p>
                        <p class="text-sm text-base-content/50 mt-2">
                            Supports multiple Albert Heijn receipt PDFs (max 10MB each)
                        </p>
                    </div>
                </template>

                <template x-if="!isUploading && files.length > 0">
                    <div @click.stop>
                        <p class="text-lg text-success font-medium mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-text="files.length + ' file' + (files.length !== 1 ? 's' : '') + ' selected'"></span>
                        </p>
                        <p class="text-sm text-base-content/50">
                            Click or drop more files to add, or click a file to remove
                        </p>
                    </div>
                </template>

                <template x-if="isUploading">
                    <div @click.stop>
                        <span class="loading loading-spinner loading-lg text-primary"></span>
                        <p class="text-lg text-base-content/80 font-medium mt-4">
                            Processing <span x-text="currentIndex + 1"></span> of <span x-text="files.length"></span> receipts...
                        </p>
                        <progress class="progress progress-primary w-64 mt-2" :value="currentIndex + 1" :max="files.length"></progress>
                    </div>
                </template>
            </div>

            <!-- File List -->
            <div class="mt-4 space-y-2" x-show="files.length > 0 || fileErrors.length > 0">
                <!-- Valid files -->
                <template x-for="(file, index) in files" :key="'file-' + index">
                    <div class="flex items-center gap-2 py-2 px-3 bg-base-200 rounded-lg">
                        <!-- Status icon -->
                        <template x-if="!isUploading || index > currentIndex">
                            <svg class="w-5 h-5 text-base-content/40 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </template>
                        <template x-if="isUploading && index === currentIndex">
                            <span class="loading loading-spinner loading-sm text-primary flex-shrink-0"></span>
                        </template>
                        <template x-if="isUploading && index < currentIndex && results[index] && (results[index].status === 'success' || results[index].status === 'partial')">
                            <svg class="w-5 h-5 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="isUploading && index < currentIndex && results[index] && results[index].status === 'duplicate'">
                            <svg class="w-5 h-5 text-warning flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </template>
                        <template x-if="isUploading && index < currentIndex && results[index] && results[index].status === 'failed'">
                            <svg class="w-5 h-5 text-error flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </template>

                        <!-- Filename -->
                        <span x-text="file.name" class="text-sm flex-grow truncate"></span>

                        <!-- Size -->
                        <span x-text="(file.size / 1024 / 1024).toFixed(2) + ' MB'" class="text-xs text-base-content/50 flex-shrink-0"></span>

                        <!-- Remove button (only when not uploading) -->
                        <button
                            x-show="!isUploading"
                            @click.stop="removeFile(index)"
                            class="btn btn-ghost btn-xs btn-circle"
                            title="Remove file"
                            :aria-label="'Remove file ' + file.name"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- Invalid files -->
                <template x-for="(fileError, index) in fileErrors" :key="'error-' + index">
                    <div class="flex items-center gap-2 py-2 px-3 bg-error/10 rounded-lg">
                        <svg class="w-5 h-5 text-error flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span x-text="fileError.name" class="text-sm text-error flex-grow truncate"></span>
                        <span x-text="fileError.error" class="text-xs text-error/70"></span>
                        <button
                            @click.stop="clearFileError(index)"
                            class="btn btn-ghost btn-xs btn-circle text-error"
                            title="Dismiss"
                            :aria-label="'Dismiss error for ' + fileError.name"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Upload Button -->
            <div class="flex justify-end mt-4" x-show="files.length > 0">
                <button
                    type="button"
                    @click="submitBatch()"
                    :disabled="files.length === 0 || isUploading"
                    class="btn btn-primary"
                >
                    <template x-if="!isUploading">
                        <span>
                            Upload <span x-text="files.length"></span> Receipt<span x-text="files.length !== 1 ? 's' : ''"></span>
                        </span>
                    </template>
                    <template x-if="isUploading">
                        <span class="loading loading-spinner loading-sm"></span>
                    </template>
                </button>
            </div>
        </div>
    </template>

    <!-- Results Summary -->
    <template x-if="isComplete">
        <div class="space-y-6" role="status" aria-live="polite" aria-label="Upload results">
            <!-- Stats -->
            <div class="stats stats-vertical lg:stats-horizontal shadow w-full">
                <div class="stat">
                    <div class="stat-figure text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="stat-title">Imported</div>
                    <div class="stat-value text-success" x-text="successCount"></div>
                    <div class="stat-desc">receipts processed</div>
                </div>

                <div class="stat" x-show="duplicateCount > 0">
                    <div class="stat-figure text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="stat-title">Duplicates</div>
                    <div class="stat-value text-warning" x-text="duplicateCount"></div>
                    <div class="stat-desc">already imported</div>
                </div>

                <div class="stat" x-show="failedCount > 0">
                    <div class="stat-figure text-error">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div class="stat-title">Failed</div>
                    <div class="stat-value text-error" x-text="failedCount"></div>
                    <div class="stat-desc">could not be parsed</div>
                </div>
            </div>

            <!-- Detailed Results -->
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h3 class="card-title text-base">Upload Details</h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <template x-for="(result, index) in results" :key="'result-' + index">
                            <div class="flex items-start gap-3 py-2 border-b border-base-200 last:border-b-0">
                                <!-- Status icon -->
                                <template x-if="getStatusIcon(result) === 'success'">
                                    <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </template>
                                <template x-if="getStatusIcon(result) === 'warning'">
                                    <svg class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </template>
                                <template x-if="getStatusIcon(result) === 'error'">
                                    <svg class="w-5 h-5 text-error flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </template>

                                <div class="flex-grow min-w-0">
                                    <p class="text-sm font-medium truncate" x-text="result.filename"></p>
                                    <template x-if="result.status === 'success' || result.status === 'partial'">
                                        <p class="text-xs text-base-content/60">
                                            <span x-text="result.item_count"></span> items,
                                            <span x-text="'€' + parseFloat(result.total_amount).toFixed(2)"></span>
                                            <template x-if="result.unmatched_bonus_count > 0">
                                                <span class="text-warning">
                                                    (<span x-text="result.unmatched_bonus_count"></span> unmatched bonuses)
                                                </span>
                                            </template>
                                        </p>
                                    </template>
                                    <template x-if="result.status === 'duplicate'">
                                        <p class="text-xs text-warning" x-text="result.message"></p>
                                    </template>
                                    <template x-if="result.status === 'failed'">
                                        <p class="text-xs text-error" x-text="result.message || (result.errors ? result.errors.join(', ') : 'Unknown error')"></p>
                                    </template>
                                </div>

                                <!-- View link for successful imports -->
                                <template x-if="result.receipt_id">
                                    <a
                                        :href="'/receipts/' + result.receipt_id"
                                        class="btn btn-ghost btn-xs"
                                    >
                                        View
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-3 justify-end">
                <button @click="reset()" class="btn btn-outline">
                    Upload More
                </button>
                <a :href="receiptsUrl" class="btn btn-primary">
                    View All Receipts
                </a>
            </div>
        </div>
    </template>
</div>
