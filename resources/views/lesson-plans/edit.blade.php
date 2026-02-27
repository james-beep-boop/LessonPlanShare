<x-layout>
    <x-slot:title>Create New Version — {{ $lessonPlan->class_name }} — ARES Education</x-slot>

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Create New Version</h1>
        <p class="text-sm text-gray-600 mb-6">
            You are creating a new version based on
            <strong>{{ $lessonPlan->class_name }} Day {{ $lessonPlan->lesson_day }}</strong> (Version {{ $lessonPlan->semantic_version }})
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
                    Download Version {{ $lessonPlan->semantic_version }} ({{ $lessonPlan->file_name }})
                </a>
            </div>
        @endif

        <form method="POST" action="{{ route('lesson-plans.update', $lessonPlan) }}" enctype="multipart/form-data"
              class="border border-gray-200 rounded-lg p-6 space-y-5">
            @csrf
            @method('PUT')

            {{-- Class Name (dropdown with "Other" option for new classes) --}}
            @php
                $currentClass = old('class_name', $lessonPlan->class_name);
                $isKnownClass = in_array($currentClass, $classNames);
            @endphp
            <div x-data="{
                    selected: '{{ $isKnownClass ? $currentClass : '__other__' }}',
                    custom: '{{ !$isKnownClass ? $currentClass : '' }}',
                    isOther: {{ !$isKnownClass && $currentClass ? 'true' : 'false' }}
                 }">
                <label for="edit_class_name_select" class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                <select id="edit_class_name_select"
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

                <div x-show="isOther" x-cloak class="mt-2">
                    <input type="text" x-model="custom"
                           placeholder="Enter new class name"
                           maxlength="100"
                           @change="$dispatch('lesson-meta-changed')"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                </div>

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

            {{-- Revision Type + Version Preview --}}
            {{-- This x-data scope reads class_name/lesson_day from the DOM and fetches the
                 computed next version via AJAX. It initialises with server-precomputed values
                 to avoid a round-trip delay on page load. @lesson-meta-changed events are
                 dispatched by the class/day selects above when their values change. --}}
            <div x-data="versionPreview(
                    '{{ addslashes($nextMajorVersion) }}',
                    '{{ addslashes($nextMinorVersion) }}'
                 )"
                 x-init="setFromPrecomputed()"
                 @lesson-meta-changed.window="refresh()">

                <label class="block text-sm font-medium text-gray-700 mb-2">Revision Type *</label>
                <div class="space-y-2">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="radio" name="revision_type" value="major"
                               x-model="revisionType"
                               @change="setFromPrecomputed()"
                               {{ old('revision_type', 'major') === 'major' ? 'checked' : '' }}
                               class="mt-0.5 border-gray-300 text-gray-900 focus:ring-gray-400">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Major revision</span>
                            <span class="text-xs text-gray-400 ml-1">— new approach or topic (bumps middle number, resets last)</span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="radio" name="revision_type" value="minor"
                               x-model="revisionType"
                               @change="setFromPrecomputed()"
                               {{ old('revision_type') === 'minor' ? 'checked' : '' }}
                               class="mt-0.5 border-gray-300 text-gray-900 focus:ring-gray-400">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Minor revision</span>
                            <span class="text-xs text-gray-400 ml-1">— small fix or improvement (bumps last number only)</span>
                        </div>
                    </label>
                </div>
                @error('revision_type') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror

                <p class="mt-2.5 text-xs text-gray-500">
                    This version will be assigned:
                    <span class="font-mono font-semibold text-gray-800 ml-1"
                          x-text="loading ? '…' : version"></span>
                </p>
            </div>

            {{-- File Upload + Submit (wrapped together so the button can react to fileSelected state) --}}
            <div x-data="fileValidator()">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Revised Lesson Plan File *</label>
                <input type="file" name="file" id="file" required
                       accept=".doc,.docx,.txt,.rtf,.odt"
                       @change="validate($event)"
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                              file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                <p class="text-xs text-gray-500 mt-1">Upload your revised version. Max 1 MB.</p>
                <p x-show="error" x-text="error" x-cloak class="text-red-600 text-xs mt-1"></p>
                @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror

                {{-- Submit: greyed out until a valid file is chosen --}}
                <div class="flex items-center space-x-3 pt-4">
                    <button type="submit"
                            :disabled="!fileSelected"
                            :class="fileSelected ? 'bg-gray-900 hover:bg-gray-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'"
                            class="px-5 py-2 text-white text-sm font-medium rounded-md transition-colors">
                        Upload New Version
                    </button>
                    <a href="{{ route('lesson-plans.show', $lessonPlan) }}" class="text-sm text-gray-500 hover:text-gray-900">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Client-side file validation (size + type check before upload) --}}
    <script>
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

        /**
         * Alpine.js component for version preview.
         *
         * Initialised with server-precomputed major/minor versions to avoid a
         * round-trip on page load. When the user changes class/day or revision_type,
         * it fetches the updated version from the nextVersion AJAX endpoint.
         *
         * The 'lesson-meta-changed' window event is dispatched by the class_name
         * and lesson_day selects above whenever their value changes.
         */
        function versionPreview(precomputedMajor, precomputedMinor) {
            return {
                version: precomputedMajor,
                revisionType: 'major',
                precomputedMajor: precomputedMajor,
                precomputedMinor: precomputedMinor,
                loading: false,
                parentClassName: @js($lessonPlan->class_name),
                parentLessonDay: {{ $lessonPlan->lesson_day }},

                // Use precomputed values when class/day match the parent plan;
                // otherwise fetch from the server (user has changed them).
                setFromPrecomputed() {
                    const className = document.querySelector('[name="class_name"]')?.value || '';
                    const lessonDay = parseInt(document.querySelector('[name="lesson_day"]')?.value || '0');
                    if (className === this.parentClassName && lessonDay === this.parentLessonDay) {
                        this.version = this.revisionType === 'minor'
                            ? this.precomputedMinor
                            : this.precomputedMajor;
                    } else {
                        this.refresh();
                    }
                },

                async refresh() {
                    const className = document.querySelector('[name="class_name"]')?.value || '';
                    const lessonDay = document.querySelector('[name="lesson_day"]')?.value || '';
                    if (!className || !lessonDay) { this.version = '—'; return; }
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            class_name: className,
                            lesson_day: lessonDay,
                            revision_type: this.revisionType
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
                        // On error fall back to precomputed for the current revision type
                        this.version = this.revisionType === 'minor'
                            ? this.precomputedMinor
                            : this.precomputedMajor;
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>

</x-layout>
