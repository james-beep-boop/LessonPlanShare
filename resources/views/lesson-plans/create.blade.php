<x-layout>
    <x-slot:title>Upload New Lesson Plan — ARES Education</x-slot>

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Upload a New Lesson Plan</h1>

        <form method="POST" action="{{ route('lesson-plans.store') }}" enctype="multipart/form-data"
              class="border border-gray-200 rounded-lg p-6 space-y-5">
            @csrf

            {{-- Class Name (dropdown) --}}
            <div>
                <label for="class_name" class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                <select name="class_name" id="class_name" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                    <option value="">— Select a class —</option>
                    @foreach ($classNames as $cn)
                        <option value="{{ $cn }}" {{ old('class_name') === $cn ? 'selected' : '' }}>{{ $cn }}</option>
                    @endforeach
                </select>
                @error('class_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Lesson Number (dropdown 1–20) --}}
            <div>
                <label for="lesson_day" class="block text-sm font-medium text-gray-700 mb-1">Lesson Number *</label>
                <select name="lesson_day" id="lesson_day" required
                        class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                    <option value="">—</option>
                    @foreach ($lessonNumbers as $num)
                        <option value="{{ $num }}" {{ (int) old('lesson_day') === $num ? 'selected' : '' }}>{{ $num }}</option>
                    @endforeach
                </select>
                @error('lesson_day') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Author (locked to logged-in user) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                <p class="w-full border border-gray-200 bg-gray-50 rounded-md px-3 py-2 text-sm text-gray-700">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Plans are always uploaded under your account.</p>
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="4"
                          placeholder="Briefly describe the lesson plan, objectives, grade level, etc."
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Generated Name Preview --}}
            <div class="bg-gray-50 border border-gray-200 rounded-md p-3">
                <p class="text-xs font-medium text-gray-600 mb-1">Document Name (auto-generated)</p>
                <p class="text-xs text-gray-500">
                    Your document will be named using the format:<br>
                    <code class="bg-gray-100 px-1 rounded text-gray-700">{ClassName}_Day{N}_{AuthorName}_{UTC-Timestamp}</code>
                </p>
            </div>

            {{-- File Upload --}}
            <div x-data="fileValidator()">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Lesson Plan File *</label>
                <input type="file" name="file" id="file" required
                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.rtf,.odt,.odp,.ods"
                       @change="validate($event)"
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                              file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                <p class="text-xs text-gray-500 mt-1">Max 10 MB. Accepted: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, RTF, ODT, ODP, ODS.</p>
                <p x-show="error" x-text="error" x-cloak class="text-red-600 text-xs mt-1"></p>
                @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center space-x-3 pt-2">
                <button type="submit"
                        class="px-5 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                    Upload Lesson Plan
                </button>
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>

    {{-- Client-side file validation (size + type check before upload) --}}
    <script>
        function fileValidator() {
            const maxSize = 10 * 1024 * 1024; // 10 MB
            const allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','rtf','odt','odp','ods'];
            return {
                error: '',
                validate(event) {
                    this.error = '';
                    const file = event.target.files[0];
                    if (!file) return;
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (!allowed.includes(ext)) {
                        this.error = 'File type ".' + ext + '" is not accepted. Please choose a supported format.';
                        event.target.value = '';
                        return;
                    }
                    if (file.size > maxSize) {
                        const sizeMB = (file.size / 1024 / 1024).toFixed(1);
                        this.error = 'File is ' + sizeMB + ' MB — the limit is 10 MB. Please choose a smaller file.';
                        event.target.value = '';
                    }
                }
            };
        }
    </script>

</x-layout>
