<x-layout>
    <x-slot:title>Create New Version — {{ $lessonPlan->class_name }} — ARES Education</x-slot>

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Create New Version</h1>
        <p class="text-sm text-gray-600 mb-6">
            You are creating a new version based on
            <strong>{{ $lessonPlan->class_name }} Day {{ $lessonPlan->lesson_day }}</strong> (v{{ $lessonPlan->version_number }})
            by {{ $lessonPlan->author->name ?? 'Unknown' }}.
        </p>

        {{-- Download the current version first --}}
        @if ($lessonPlan->file_path)
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4 mb-6">
                <p class="text-sm text-gray-700">
                    <strong>Step 1:</strong> Download the current version, make your improvements, then upload the revised file below.
                </p>
                <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                   class="inline-block mt-2 px-4 py-1.5 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                    Download v{{ $lessonPlan->version_number }} ({{ $lessonPlan->file_name }})
                </a>
            </div>
        @endif

        <form method="POST" action="{{ route('lesson-plans.update', $lessonPlan) }}" enctype="multipart/form-data"
              class="border border-gray-200 rounded-lg p-6 space-y-5">
            @csrf
            @method('PUT')

            {{-- Class Name (dropdown) --}}
            <div>
                <label for="class_name" class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                <select name="class_name" id="class_name" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                    <option value="">— Select a class —</option>
                    @foreach ($classNames as $cn)
                        <option value="{{ $cn }}" {{ old('class_name', $lessonPlan->class_name) === $cn ? 'selected' : '' }}>{{ $cn }}</option>
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
                        <option value="{{ $num }}" {{ (int) old('lesson_day', $lessonPlan->lesson_day) === $num ? 'selected' : '' }}>{{ $num }}</option>
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
                          placeholder="Describe what you changed or improved..."
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">{{ old('description', $lessonPlan->description) }}</textarea>
                @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- File Upload --}}
            <div x-data="fileValidator()">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Revised Lesson Plan File *</label>
                <input type="file" name="file" id="file" required
                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.rtf,.odt,.odp,.ods"
                       @change="validate($event)"
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                              file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                <p class="text-xs text-gray-500 mt-1">Upload your revised version. Max 1 MB.</p>
                <p x-show="error" x-text="error" x-cloak class="text-red-600 text-xs mt-1"></p>
                @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center space-x-3 pt-2">
                <button type="submit"
                        class="px-5 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                    Upload New Version
                </button>
                <a href="{{ route('lesson-plans.show', $lessonPlan) }}" class="text-sm text-gray-500 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>

    {{-- Client-side file validation (size + type check before upload) --}}
    <script>
        function fileValidator() {
            const maxSize = 1 * 1024 * 1024; // 1 MB
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
                        this.error = 'File is ' + sizeMB + ' MB — the limit is 1 MB. Please choose a smaller file.';
                        event.target.value = '';
                    }
                }
            };
        }
    </script>

</x-layout>
