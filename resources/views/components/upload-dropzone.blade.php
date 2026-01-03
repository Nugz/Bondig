<div
    x-data="{
        isDragging: false,
        isUploading: false,
        fileName: '',
        fileError: '',
        handleDrop(e) {
            this.isDragging = false;
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.validateAndSetFile(files[0]);
            }
        },
        handleFileSelect(e) {
            if (e.target.files.length > 0) {
                this.validateAndSetFile(e.target.files[0]);
            }
        },
        validateAndSetFile(file) {
            this.fileError = '';
            const maxSizeBytes = 10 * 1024 * 1024; // 10MB
            if (file.type !== 'application/pdf') {
                this.fileError = 'Only PDF files are accepted';
                this.fileName = '';
                return;
            }
            if (file.size > maxSizeBytes) {
                this.fileError = 'File size exceeds 10MB limit';
                this.fileName = '';
                return;
            }
            this.fileName = file.name;
            this.$refs.fileInput.files = this.createFileList(file);
        },
        createFileList(file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            return dt.files;
        },
        submitForm() {
            if (!this.fileName || this.fileError) return;
            this.isUploading = true;
            this.$refs.uploadForm.submit();
        }
    }"
    class="w-full"
>
    <form
        x-ref="uploadForm"
        action="{{ route('upload.store') }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)"
            @click="$refs.fileInput.click()"
            :class="{
                'border-primary bg-primary/5': isDragging,
                'border-error': fileError,
                'border-success': fileName && !fileError
            }"
            class="border-2 border-dashed border-base-300 rounded-lg p-8 text-center cursor-pointer transition-all duration-200 hover:border-primary/50 hover:bg-base-100"
            style="min-height: 300px;"
        >
            <input
                type="file"
                name="pdf"
                x-ref="fileInput"
                @change="handleFileSelect($event)"
                accept="application/pdf"
                class="hidden"
            >

            <template x-if="!isUploading">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-primary/60 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>

                    <template x-if="!fileName && !fileError">
                        <div>
                            <p class="text-lg text-base-content/80 font-medium">
                                Drag and drop your AH receipt here, or click to browse
                            </p>
                            <p class="text-sm text-base-content/50 mt-2">
                                Supports Albert Heijn receipt PDFs (max 10MB)
                            </p>
                        </div>
                    </template>

                    <template x-if="fileName && !fileError">
                        <div>
                            <p class="text-lg text-success font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-text="fileName"></span>
                            </p>
                            <p class="text-sm text-base-content/50 mt-2">
                                Click or drop another file to replace
                            </p>
                        </div>
                    </template>

                    <template x-if="fileError">
                        <div>
                            <p class="text-lg text-error font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span x-text="fileError"></span>
                            </p>
                            <p class="text-sm text-base-content/50 mt-2">
                                Please select a PDF file
                            </p>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="isUploading">
                <div>
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                    <p class="text-lg text-base-content/80 font-medium mt-4">
                        Parsing receipt...
                    </p>
                    <p class="text-sm text-base-content/50 mt-2">
                        Extracting products and prices
                    </p>
                </div>
            </template>
        </div>

        @error('pdf')
            <p class="text-error text-sm mt-2">{{ $message }}</p>
        @enderror

        <div class="flex justify-end mt-4">
            <button
                type="button"
                @click="submitForm()"
                :disabled="!fileName || fileError || isUploading"
                class="btn btn-primary"
            >
                <template x-if="!isUploading">
                    <span>Upload Receipt</span>
                </template>
                <template x-if="isUploading">
                    <span class="loading loading-spinner loading-sm"></span>
                </template>
            </button>
        </div>
    </form>
</div>
