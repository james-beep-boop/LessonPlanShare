<x-layout>
    <x-slot:title>Upload Revision — {{ $lessonPlan->class_name }} — ARES Education</x-slot>

    @php
        $currentClass = old('class_name', $lessonPlan->class_name);
        $isKnownClass = in_array($currentClass, $classNames);
    @endphp

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Upload New Version</h1>

        {{--
            Top-level Alpine component manages:
              confirmed       — whether the user has ticked the "this is the file I'm revising" checkbox
              classUnlocked   — whether Class Name is editable
              dayUnlocked     — whether Lesson Number is editable
              descUnlocked    — whether Description is editable
              classSelected   — current value of the class dropdown
              classCustom     — free-text class name when "Other" is selected
              classIsOther    — true when dropdown is on "Other (enter new class name)"
              lessonDay       — current lesson day value (string; matches option values)
              description     — current description text (bound via x-model on textarea)
              computedClassName — getter: resolves the correct class_name to submit
        --}}
        <div x-data="{
                confirmed: @js($errors->any()),

                classUnlocked: false,
                classSelected: @js($isKnownClass ? $currentClass : '__other__'),
                classCustom:   @js(!$isKnownClass ? $currentClass : ''),
                classIsOther:  @js(!$isKnownClass),

                dayUnlocked: false,
                lessonDay:   '{{ old('lesson_day', $lessonPlan->lesson_day) }}',

                descUnlocked: false,
                description:  @js(old('description', $lessonPlan->description ?? '')),

                get computedClassName() {
                    if (this.classSelected === '__other__') return this.classCustom;
                    return this.classSelected;
                }
             }">

            {{-- Step 2: Confirm the source document --}}
            {{-- The rest of the page is hidden until this checkbox is ticked. --}}
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

            {{-- The form — hidden until the confirmation checkbox above is ticked --}}
            <form method="POST" action="{{ route('lesson-plans.store-version', $lessonPlan) }}"
                  enctype="multipart/form-data"
                  x-show="confirmed" x-cloak
                  class="border border-gray-200 rounded-lg p-6 space-y-5">
                @csrf

                {{-- Contributor (always read-only — locked to the logged-in user) --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Contributor</p>
                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Revisions are always uploaded under your account name.</p>
                </div>

                {{-- Checkbox-locked fields ────────────────────────────────────────── --}}
                {{-- Each field is pre-filled from the original document and locked.    --}}
                {{-- The user must tick the checkbox next to a field to edit it.        --}}
                <div class="border-t border-gray-100 pt-5 space-y-5">
                    <p class="text-sm font-semibold text-gray-700">Confirm (or change) the following about your revision:</p>

                    {{-- Class Name --}}
                    {{-- Checkbox + label + control all on one row via flex-wrap.        --}}
                    {{-- On small screens the control wraps to the next line naturally.  --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <label class="flex items-center gap-2 cursor-pointer shrink-0">
                                <input type="checkbox" x-model="classUnlocked"
                                       class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                                <span class="text-sm font-medium text-gray-700">Class Name</span>
                                <span class="text-xs text-gray-400 italic">(check to change)</span>
                            </label>

                            {{-- Locked: read-only pill on same row --}}
                            <p x-show="!classUnlocked"
                               class="flex-1 bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-sm text-gray-700 min-w-0">
                                {{ $lessonPlan->class_name }}
                            </p>

                            {{-- Unlocked: editable dropdown on same row; "Other" text input stacks below --}}
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
                        </div>
                        <input type="hidden" name="class_name" :value="computedClassName">
                        @error('class_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Lesson Number --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <label class="flex items-center gap-2 cursor-pointer shrink-0">
                                <input type="checkbox" x-model="dayUnlocked"
                                       class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                                <span class="text-sm font-medium text-gray-700">Lesson Number</span>
                                <span class="text-xs text-gray-400 italic">(check to change)</span>
                            </label>

                            {{-- Locked: read-only pill on same row --}}
                            <p x-show="!dayUnlocked"
                               class="bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-sm text-gray-700">
                                {{ $lessonPlan->lesson_day }}
                            </p>

                            {{-- Unlocked: select on same row --}}
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
                        </div>
                        <input type="hidden" name="lesson_day" :value="lessonDay">
                        @error('lesson_day') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    {{-- Label row is inline; textarea expands below when unlocked.        --}}
                    {{-- A hidden input submits the value when locked (textarea disabled). --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <label class="flex items-center gap-2 cursor-pointer shrink-0">
                                <input type="checkbox" x-model="descUnlocked"
                                       class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                                <span class="text-sm font-medium text-gray-700">Description</span>
                                <span class="text-xs text-gray-400 italic">(check to change)</span>
                            </label>

                            {{-- Locked: single-line truncated preview on same row --}}
                            <p x-show="!descUnlocked"
                               x-text="description || '(No description)'"
                               class="flex-1 bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-sm text-gray-500 truncate min-w-0"></p>
                        </div>

                        {{-- Hidden input submits when locked; textarea takes over when unlocked --}}
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
                {{-- Nested x-data so the version preview has its own loading/refresh state. --}}
                {{-- It listens for 'lesson-meta-changed' events dispatched by class/day changes. --}}
                {{-- It reads class_name and lesson_day from the hidden inputs via querySelector.  --}}
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

                {{-- File Upload --}}
                {{-- Native file input is visually hidden; a styled <label> acts as the trigger. --}}
                {{-- This lets us control the button text ("Choose Your Lesson Plan Revision").  --}}
                <div x-data="fileValidator()">
                    <div class="flex flex-wrap items-center gap-3">
                        <label for="file"
                               class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300
                                      text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200
                                      cursor-pointer transition-colors select-none">
                            Choose Your Lesson Plan Revision
                        </label>
                        <input type="file" name="file" id="file" required
                               accept=".doc,.docx,.txt,.rtf,.odt"
                               @change="validate($event)"
                               class="sr-only">
                        {{-- Shows the selected filename after a valid file is chosen --}}
                        <span x-show="fileName" x-text="fileName" x-cloak
                              class="text-sm text-gray-600 font-mono truncate max-w-xs"></span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1.5">Max 1 MB. Accepted: .doc, .docx, .txt, .rtf, .odt</p>
                    <p x-show="error" x-text="error" x-cloak class="text-red-600 text-xs mt-1"></p>
                    @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror

                    {{-- Submit button: greyed and disabled until a valid file has been chosen --}}
                    <div class="flex items-center space-x-3 pt-4">
                        <button type="submit"
                                :disabled="!fileSelected"
                                :class="fileSelected ? 'bg-gray-900 hover:bg-gray-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'"
                                class="px-5 py-2 text-white text-sm font-medium rounded-md transition-colors">
                            Upload New Version
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
         * Also tracks the selected filename for display next to the button.
         */
        function fileValidator() {
            const maxSize = 1 * 1024 * 1024; // 1 MB
            const allowed = ['doc','docx','txt','rtf','odt']; // must match StoreVersionRequest
            return {
                error: '',
                fileSelected: false,
                fileName: '',
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
         *
         * Initialised with server-precomputed major/minor versions to avoid a
         * round-trip on page load. When class_name or lesson_day change (via the
         * 'lesson-meta-changed' window event), it re-fetches from the nextVersion
         * AJAX endpoint.
         *
         * Reads class_name and lesson_day from the hidden inputs in the form via
         * querySelector — these inputs are always present in the DOM and always
         * hold the currently-effective values (whether locked or unlocked).
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

                // Use server-precomputed values when class/day match the parent plan;
                // fetch from server when the user has unlocked and changed them.
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
                            class_name:    className,
                            lesson_day:    lessonDay,
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
                        // On network error, fall back to precomputed for the current revision type
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
