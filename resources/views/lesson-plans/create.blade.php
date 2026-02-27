<x-layout>
    <x-slot:title>Upload New Lesson Plan — ARES Education</x-slot>

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Upload a New Lesson Plan</h1>

        <form method="POST" action="{{ route('lesson-plans.store') }}" enctype="multipart/form-data"
              class="border border-gray-200 rounded-lg p-6 space-y-5">
            @csrf

            {{-- Class Name (dropdown with "Other" option for new classes) --}}
            <div x-data="{
                    selected: @js(old('class_name_select', old('class_name', ''))),
                    custom: @js(old('custom_class_name', '')),
                    isOther: {{ old('class_name_select') === '__other__' || (old('class_name') && !in_array(old('class_name'), $classNames)) ? 'true' : 'false' }}
                 }">
                <label for="class_name_select" class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                <select id="class_name_select"
                        x-model="selected"
                        @change="isOther = (selected === '__other__'); if (!isOther) custom = ''; $dispatch('lesson-meta-changed')"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                    <option value="">— Select a class —</option>
                    @foreach ($classNames as $cn)
                        <option value="{{ $cn }}">{{ $cn }}</option>
                    @endforeach
                    <option value="__other__">Other (enter new class name)</option>
                </select>

                {{-- Text input for custom class name (shown when "Other" is selected) --}}
                <div x-show="isOther" x-cloak class="mt-2">
                    <input type="text" x-model="custom"
                           placeholder="Enter new class name"
                           maxlength="100"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">This will create a new class in the system.</p>
                </div>

                {{-- Hidden input sends the actual class_name value to the server --}}
                <input type="hidden" name="class_name"
                       :value="isOther ? custom : selected">

                @error('class_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Lesson Number (dropdown 1–20) --}}
            <div>
                <label for="lesson_day" class="block text-sm font-medium text-gray-700 mb-1">Lesson Number *</label>
                <select name="lesson_day" id="lesson_day" required
                        @change="$dispatch('lesson-meta-changed')"
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

            {{-- Version Preview --}}
            {{-- Fetches the computed next version for the selected class/day via AJAX.
                 Updates whenever class_name or lesson_day change (via lesson-meta-changed event). --}}
            <div x-data="createVersionPreview()" x-init="refresh()" @lesson-meta-changed.window="refresh()"
                 class="bg-gray-50 border border-gray-200 rounded-md p-3">
                <p class="text-xs font-medium text-gray-600 mb-1">Version (auto-assigned)</p>
                <p class="text-xs text-gray-500">
                    This upload will be assigned version
                    <span class="font-mono font-semibold text-gray-800" x-text="loading ? '…' : version"></span>
                    for the selected class and lesson day.
                </p>
            </div>

            {{-- File Upload + Submit (wrapped together so the button can react to fileSelected state) --}}
            <div x-data="fileValidator()">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Lesson Plan File *</label>
                <input type="file" name="file" id="file" required
                       accept=".doc,.docx,.txt,.rtf,.odt"
                       @change="validate($event)"
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                              file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                <p class="text-xs text-gray-500 mt-1">Max 1 MB. Accepted: DOC, DOCX, TXT, RTF, ODT.</p>
                <p x-show="error" x-text="error" x-cloak class="text-red-600 text-xs mt-1"></p>
                @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror

                {{-- Submit: greyed out until a valid file is chosen --}}
                <div class="flex items-center space-x-3 pt-4">
                    <button type="submit"
                            :disabled="!fileSelected"
                            :class="fileSelected ? 'bg-gray-900 hover:bg-gray-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'"
                            class="px-5 py-2 text-white text-sm font-medium rounded-md transition-colors">
                        Upload Lesson Plan
                    </button>
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Client-side scripts: file validation + version preview --}}
    <script>
        /**
         * Fetches the computed next semantic version for the currently-selected
         * class/day from the AJAX endpoint. New uploads always use 'major'.
         * Returns 1.0.0 if no class/day is selected yet.
         */
        function createVersionPreview() {
            return {
                version: '1.0.0',
                loading: false,
                async refresh() {
                    const className = document.querySelector('[name="class_name"]')?.value || '';
                    const lessonDay = document.querySelector('[name="lesson_day"]')?.value || '';
                    if (!className || !lessonDay) { this.version = '1.0.0'; return; }
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            class_name: className,
                            lesson_day: lessonDay,
                            revision_type: 'major'
                        });
                        const r = await fetch('{{ route('lesson-plans.next-version') }}?' + params, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        });
                        const d = await r.json();
                        this.version = d.version;
                    } catch (e) {
                        this.version = '1.0.0';
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }

        function fileValidator() {
            const maxSize = 1 * 1024 * 1024; // 1 MB
            const allowed = ['doc','docx','txt','rtf','odt']; // must match StoreLessonPlanRequest
            return {
                error: '',
                fileSelected: false,
                validate(event) {
                    this.fileSelected = false; // reset on every change
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
                        return;
                    }
                    this.fileSelected = true; // only set after all validations pass
                }
            };
        }
    </script>

</x-layout>
