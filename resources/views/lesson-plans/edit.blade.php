<x-layout>
    <x-slot:title>Upload Revision — {{ $lessonPlan->class_name }} Grade {{ $lessonPlan->grade }} Lesson {{ $lessonPlan->lesson_day }} — ARES Education</x-slot>

    @php
        $currentClass = old('class_name', $lessonPlan->class_name);
        $isKnownClass = in_array($currentClass, $classNames);
        $isAdmin      = auth()->user()?->is_admin;
    @endphp

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">
            Upload Revision of {{ $lessonPlan->class_name }}, Grade {{ $lessonPlan->grade }}, Lesson {{ $lessonPlan->lesson_day }}
        </h1>

        <div x-data="{
                confirmed: @js($errors->any()),

                classUnlocked: false,
                classSelected: @js($isKnownClass ? $currentClass : '__other__'),
                classCustom:   @js(!$isKnownClass ? $currentClass : ''),
                classIsOther:  @js(!$isKnownClass),

                dayUnlocked: false,
                lessonDay:   '{{ old('lesson_day', $lessonPlan->lesson_day) }}',

                gradeUnlocked: false,
                grade: '{{ old('grade', $lessonPlan->grade ?? 10) }}',

                descUnlocked: false,
                description:  @js(old('description', $lessonPlan->description ?? '')),

                get computedClassName() {
                    if (this.classSelected === '__other__') return this.classCustom;
                    return this.classSelected;
                }
             }">

            {{-- Confirm the source document before showing the form --}}
            <div class="border border-gray-200 rounded-lg p-5 mb-4">
                <p class="text-sm font-medium text-gray-700 mb-3">The lesson plan revision that you are about to upload:</p>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" x-model="confirmed"
                           class="mt-0.5 h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                    <span class="text-sm text-gray-900 font-mono break-all">{{ $lessonPlan->file_name ?? $lessonPlan->name }}</span>
                </label>
                <p x-show="!confirmed" class="text-xs text-gray-400 mt-2 ml-7">
                    Check the box above to continue.
                </p>
            </div>

            <form method="POST" action="{{ route('lesson-plans.store-version', $lessonPlan) }}"
                  enctype="multipart/form-data"
                  x-show="confirmed" x-cloak
                  class="border border-gray-200 rounded-lg p-6 space-y-5">
                @csrf

                {{-- Contributor (always read-only) --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Contributor</p>
                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Revisions are always uploaded under your account name.</p>
                </div>

                <div class="border-t border-gray-100 pt-5 space-y-5">
                    <p class="text-sm font-semibold text-gray-700">Confirm (or change) the following about your revision:</p>

                    {{-- Class Name --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            @if ($isAdmin)
                                <label class="flex items-center gap-2 cursor-pointer shrink-0">
                                    <input type="checkbox" x-model="classUnlocked"
                                           class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                                    <span class="text-sm font-medium text-gray-700">Class Name</span>
                                    <span class="text-xs text-gray-400 italic">(check to change)</span>
                                </label>
                            @else
                                <span class="text-sm font-medium text-gray-700 shrink-0">Class Name</span>
                            @endif

                            <p x-show="!classUnlocked"
                               class="flex-1 bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-sm text-gray-700 min-w-0">
                                {{ $lessonPlan->class_name }}
                            </p>

                            @if ($isAdmin)
                                <div x-show="classUnlocked" x-cloak class="flex-1 min-w-[10rem] space-y-2">
                                    <select x-model="classSelected"
                                            @change="classIsOther = (classSelected === '__other__'); if (!classIsOther) classCustom = ''; $dispatch('lesson-meta-changed')"
                                            class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                                        <option value="">— Select a class —</option>
                                        @foreach ($classNames as $cn)
                                            <option value="{{ $cn }}">{{ $cn }}</option>
                                        @endforeach
                                        <option value="__other__">Other (enter new class name)</option>
                                    </select>
                                    <div x-show="classIsOther" x-cloak>
                                        <input type="text" x-model="classCustom"
                                               placeholder="Enter new class name" maxlength="100"
                                               @input="$dispatch('lesson-meta-changed')"
                                               class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm
                                                      focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                                    </div>
                                </div>
                            @endif
                        </div>
                        <input type="hidden" name="class_name" :value="computedClassName">
                        @error('class_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Grade Level (above Lesson Number per spec) --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            @if ($isAdmin)
                                <label class="flex items-center gap-2 cursor-pointer shrink-0">
                                    <input type="checkbox" x-model="gradeUnlocked"
                                           class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                                    <span class="text-sm font-medium text-gray-700">Grade</span>
                                    <span class="text-xs text-gray-400 italic">(check to change)</span>
                                </label>
                            @else
                                <span class="text-sm font-medium text-gray-700 shrink-0">Grade</span>
                            @endif

                            <p x-show="!gradeUnlocked"
                               class="bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-sm text-gray-700"
                               x-text="grade">
                            </p>

                            @if ($isAdmin)
                                <div x-show="gradeUnlocked" x-cloak>
                                    <select x-model="grade"
                                            @change="$dispatch('lesson-meta-changed')"
                                            class="w-24 border border-gray-300 rounded-md px-3 py-1.5 text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                    </select>
                                </div>
                            @endif
                        </div>
                        <input type="hidden" name="grade" :value="grade">
                        @error('grade') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Lesson Number --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            @if ($isAdmin)
                                <label class="flex items-center gap-2 cursor-pointer shrink-0">
                                    <input type="checkbox" x-model="dayUnlocked"
                                           class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                                    <span class="text-sm font-medium text-gray-700">Lesson Number</span>
                                    <span class="text-xs text-gray-400 italic">(check to change)</span>
                                </label>
                            @else
                                <span class="text-sm font-medium text-gray-700 shrink-0">Lesson Number</span>
                            @endif

                            <p x-show="!dayUnlocked"
                               class="bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-sm text-gray-700">
                                {{ $lessonPlan->lesson_day }}
                            </p>

                            @if ($isAdmin)
                                <div x-show="dayUnlocked" x-cloak>
                                    <select x-model="lessonDay"
                                            @change="$dispatch('lesson-meta-changed')"
                                            class="w-32 border border-gray-300 rounded-md px-3 py-1.5 text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                                        @foreach ($lessonNumbers as $num)
                                            <option value="{{ $num }}" {{ (string) $num === (string) old('lesson_day', $lessonPlan->lesson_day) ? 'selected' : '' }}>
                                                {{ $num }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                        <input type="hidden" name="lesson_day" :value="lessonDay">
                        @error('lesson_day') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description (editable by all users) --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <label class="flex items-center gap-2 cursor-pointer shrink-0">
                                <input type="checkbox" x-model="descUnlocked"
                                       class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                                <span class="text-sm font-medium text-gray-700">Description</span>
                                <span class="text-xs text-gray-400 italic">(check to change)</span>
                            </label>
                            <p x-show="!descUnlocked"
                               x-text="description || '(No description)'"
                               class="flex-1 bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-sm text-gray-500 truncate min-w-0"></p>
                        </div>
                        <input type="hidden" name="description" :value="description" :disabled="descUnlocked">
                        <textarea name="description" id="description" rows="4"
                                  x-model="description"
                                  x-show="descUnlocked" x-cloak
                                  :disabled="!descUnlocked"
                                  placeholder="Describe what you changed or improved..."
                                  class="mt-2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent"></textarea>
                        @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Revision Type + Version Preview --}}
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

                {{-- File Upload — single "Upload Revision" button --}}
                {{-- Clicking when no file is selected opens the file picker.    --}}
                {{-- After a valid file is chosen, clicking submits the form.    --}}
                <div x-data="fileValidator()">
                    <input type="file" name="file" id="file" required
                           accept=".doc,.docx,.txt,.rtf,.odt"
                           @change="validate($event)"
                           class="sr-only">

                    <p x-show="fileName" x-cloak class="text-sm text-gray-600 font-mono mb-2 truncate max-w-xs"
                       x-text="'Selected: ' + fileName"></p>
                    <p class="text-xs text-gray-500 mb-3">Max 1 MB. Accepted: .doc, .docx, .txt, .rtf, .odt</p>
                    <p x-show="error" x-text="error" x-cloak class="text-red-600 text-xs mb-2"></p>
                    @error('file') <p class="text-red-600 text-xs mb-2">{{ $message }}</p> @enderror

                    <div class="flex items-center gap-3">
                        {{-- Single button: opens picker first, submits after file selected --}}
                        <button type="button" @click="handleUpload()"
                                :class="fileSelected
                                    ? 'bg-gray-900 hover:bg-gray-700 cursor-pointer'
                                    : 'bg-gray-600 hover:bg-gray-500 cursor-pointer'"
                                class="px-5 py-2 text-white text-sm font-medium rounded-md transition-colors">
                            Upload Revision
                        </button>
                        <a href="{{ route('lesson-plans.show', $lessonPlan) }}"
                           class="text-sm text-gray-500 hover:text-gray-900">Cancel</a>
                    </div>
                </div>

            </form>
        </div>{{-- /x-data outer wrapper --}}
    </div>

    <script>
        /**
         * Client-side file validation (size + type check before upload).
         * Single-button pattern: first click opens file picker, second click submits.
         */
        function fileValidator() {
            const maxSize = 1 * 1024 * 1024; // 1 MB
            const allowed = ['doc','docx','txt','rtf','odt']; // must match StoreVersionRequest
            return {
                error: '',
                fileSelected: false,
                fileName: '',

                // If no file selected yet, open the picker.
                // Once a valid file is staged, submit the form.
                handleUpload() {
                    if (!this.fileSelected) {
                        document.getElementById('file').click();
                    } else {
                        this.$el.closest('form').submit();
                    }
                },

                validate(event) {
                    this.fileSelected = false;
                    this.error = '';
                    this.fileName = '';
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
                    this.fileSelected = true;
                    this.fileName = file.name;
                }
            };
        }

        /**
         * Alpine.js component for version preview.
         * Reads class_name / lesson_day / grade from hidden form inputs.
         * Uses server-precomputed values when unchanged; fetches AJAX on edits.
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
                parentGrade:     {{ $lessonPlan->grade ?? 10 }},

                setFromPrecomputed() {
                    const className = document.querySelector('[name="class_name"]')?.value || '';
                    const lessonDay = parseInt(document.querySelector('[name="lesson_day"]')?.value || '0');
                    const grade     = parseInt(document.querySelector('[name="grade"]')?.value || '10');
                    if (className === this.parentClassName && lessonDay === this.parentLessonDay && grade === this.parentGrade) {
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
                    const grade     = document.querySelector('[name="grade"]')?.value || '10';
                    if (!className || !lessonDay) { this.version = '—'; return; }
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            class_name:    className,
                            lesson_day:    lessonDay,
                            grade:         grade,
                            revision_type: this.revisionType
                        });
                        const r = await fetch('{{ route('lesson-plans.next-version') }}?' + params, {
                            headers: {
                                'Accept':       'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        });
                        const d = await r.json();
                        this.version = d.version;
                    } catch (e) {
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
