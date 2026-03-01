<x-layout>
    <x-slot:title>{{ $lessonPlan->name }} — ARES Education</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $lessonPlan->class_name }} — Day {{ $lessonPlan->lesson_day }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Version {{ $lessonPlan->semantic_version }}
                    &middot; by {{ $lessonPlan->author->name ?? 'No Teacher Name' }}
                    &middot; {{ $lessonPlan->created_at->format('M j, Y g:ia') }} UTC
                </p>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $lessonPlan->name }}</p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors shrink-0">
                &larr; Back to Dashboard
            </a>
        </div>

        {{-- Lesson Plan Details --}}
        <div class="border border-gray-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Lesson Plan Details</h2>

            @if ($lessonPlan->description)
                <p class="text-gray-700 text-sm mb-4">{{ $lessonPlan->description }}</p>
            @else
                <p class="text-gray-400 text-sm italic mb-4">No description provided.</p>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Class:</span>
                    <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->class_name }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Lesson Day:</span>
                    <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->lesson_day }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Version:</span>
                    <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->semantic_version }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Contributor:</span>
                    <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->author->name ?? 'No Teacher Name' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Uploaded:</span>
                    <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->created_at->format('M j, Y g:ia') }} UTC</span>
                </div>
                <div>
                    <span class="text-gray-500">Community Rating:</span>
                    @php $displayScore = $lessonPlan->vote_score; @endphp
                    <span class="font-medium ml-1 {{ $displayScore > 0 ? 'text-green-600' : ($displayScore < 0 ? 'text-red-600' : 'text-gray-400') }}">
                        {{ $displayScore > 0 ? '+' . $displayScore : $displayScore }}
                    </span>
                </div>
            </div>

            {{-- Action Buttons --}}
            {{-- Viewer clicks fire an AJAX engagement ping before the external tab opens --}}
            <div class="mt-6 pt-4 border-t border-gray-100 space-y-2" x-data="{}">

                {{-- Row 1: External viewers — open document in a new tab + track engagement --}}
                @if ($lessonPlan->file_path)
                    @php $viewerUrl = asset('storage/' . $lessonPlan->file_path); @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <a href="https://docs.google.com/gview?url={{ urlencode($viewerUrl) }}"
                           target="_blank" rel="noopener"
                           @click="fetch('{{ route('lesson-plans.track-engagement', $lessonPlan) }}', {
                               method: 'POST',
                               headers: {
                                   'Content-Type': 'application/json',
                                   'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                   'Accept': 'application/json'
                               },
                               body: JSON.stringify({ type: 'google_docs' })
                           }).catch(() => {})"
                           class="flex flex-col items-center justify-center min-h-[3.5rem] px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                            <span>View in Google Docs ↗</span>
                            <span class="text-xs font-normal opacity-75">(best for mobile)</span>
                        </a>
                        <a href="https://view.officeapps.live.com/op/view.aspx?src={{ urlencode($viewerUrl) }}"
                           target="_blank" rel="noopener"
                           @click="fetch('{{ route('lesson-plans.track-engagement', $lessonPlan) }}', {
                               method: 'POST',
                               headers: {
                                   'Content-Type': 'application/json',
                                   'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                   'Accept': 'application/json'
                               },
                               body: JSON.stringify({ type: 'ms_office' })
                           }).catch(() => {})"
                           class="flex flex-col items-center justify-center min-h-[3.5rem] px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                            <span>View in Microsoft Office ↗</span>
                            <span class="text-xs font-normal opacity-75">(best for desktop)</span>
                        </a>
                    </div>
                @endif

                {{-- Row 2: Download · Upload Your Revision (auth users only) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @if ($lessonPlan->file_path)
                        <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                           class="flex items-center justify-center min-h-[3.5rem] px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300 text-center">
                            Download This Document
                        </a>
                    @endif
                    @auth
                        <a href="{{ route('lesson-plans.new-version', $lessonPlan) }}"
                           class="flex flex-col items-center justify-center min-h-[3.5rem] px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                            <span>Upload Your Revision of</span>
                            <span>This Document</span>
                        </a>
                    @endauth
                </div>

                {{-- Row 3: Delete (author only) — full width --}}
                {{-- Alpine modal replaces native confirm() to give an exact labelled CTA --}}
                @auth
                    @if ($lessonPlan->author_id === auth()->id())
                        <div x-data="{ confirmOpen: false }">
                            <button type="button"
                                    @click="confirmOpen = true"
                                    class="w-full flex flex-col items-center justify-center min-h-[3.5rem] px-3 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-md hover:bg-red-100 transition-colors border border-red-200">
                                <span>Delete Your Version</span>
                                <span class="text-xs font-normal">This Cannot Be Undone!</span>
                            </button>

                            <div x-show="confirmOpen" x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
                                <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6"
                                     @click.away="confirmOpen = false">
                                    <p class="text-gray-900 font-medium text-center mb-6">
                                        Are you sure? This action cannot be undone
                                    </p>
                                    <div class="flex gap-3">
                                        <button type="button"
                                                @click="confirmOpen = false"
                                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                                            Cancel
                                        </button>
                                        <form method="POST"
                                              action="{{ route('lesson-plans.destroy', $lessonPlan) }}"
                                              class="flex-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                                                Yes, Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endauth

            </div>
        </div>

        {{-- ── Rate This Document ── --}}
        {{-- Shown only when the user has engaged (author / downloaded / viewed in external viewer).  --}}
        {{-- Non-engaged users see a nudge instead; guests see nothing (page requires auth+verified). --}}
        @if($hasEngaged)
            <div class="border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Rate This Document</h2>

                <div x-data="{
                    score: {{ (int) $lessonPlan->vote_score }},
                    userVote: {{ $userVote ? (int) $userVote->value : 'null' }},
                    loading: false,
                    castVote(value) {
                        if (this.loading) return;
                        this.loading = true;
                        fetch('{{ route('votes.store', $lessonPlan) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ value: value })
                        })
                        .then(r => r.ok ? r.json() : null)
                        .then(d => { if (d) { this.score = d.score; this.userVote = d.userVote; } })
                        .catch(() => {})
                        .finally(() => { this.loading = false; });
                    },
                    resetVote() {
                        if (this.loading || this.userVote === null) return;
                        this.loading = true;
                        fetch('{{ route('votes.destroy', $lessonPlan) }}', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.ok ? r.json() : null)
                        .then(d => { if (d) { this.score = d.score; this.userVote = null; } })
                        .catch(() => {})
                        .finally(() => { this.loading = false; });
                    }
                }">
                    {{-- Live score display --}}
                    <div class="flex items-center gap-3 mb-5">
                        <span class="text-3xl font-bold tabular-nums"
                              :class="score > 0 ? 'text-green-600' : (score < 0 ? 'text-red-600' : 'text-gray-400')"
                              x-text="(score > 0 ? '+' : '') + score"></span>
                        <span class="text-sm text-gray-500">community rating</span>
                    </div>

                    {{-- Three voting buttons --}}
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button @click="castVote(1)" type="button"
                                :disabled="loading"
                                :class="userVote === 1
                                    ? 'bg-green-100 border-green-400 text-green-700'
                                    : 'bg-white border-gray-300 text-gray-700 hover:bg-green-50 hover:border-green-300'"
                                class="flex-1 px-4 py-2.5 border rounded-md text-sm font-medium transition-colors disabled:opacity-60">
                            Upvote This Version
                        </button>
                        <button @click="castVote(-1)" type="button"
                                :disabled="loading"
                                :class="userVote === -1
                                    ? 'bg-red-100 border-red-400 text-red-700'
                                    : 'bg-white border-gray-300 text-gray-700 hover:bg-red-50 hover:border-red-300'"
                                class="flex-1 px-4 py-2.5 border rounded-md text-sm font-medium transition-colors disabled:opacity-60">
                            Downvote This Version
                        </button>
                        {{-- Reset Vote: greyed and disabled until the user has an active vote --}}
                        <button @click="resetVote()" type="button"
                                :disabled="userVote === null || loading"
                                :class="userVote !== null
                                    ? 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                    : 'bg-gray-50 border-gray-200 text-gray-300 cursor-not-allowed'"
                                class="flex-1 px-4 py-2.5 border rounded-md text-sm font-medium transition-colors disabled:opacity-60">
                            Reset Vote
                        </button>
                    </div>

                    <p x-show="userVote !== null" x-cloak
                       class="text-xs text-gray-400 mt-2">
                        Your vote is recorded. Click "Reset Vote" to remove it.
                    </p>
                </div>
            </div>
        @else
            {{-- Nudge: user is authenticated but hasn't engaged with the document yet --}}
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <p class="text-sm text-gray-500 text-center">
                    Open this plan in an external viewer or download it to unlock voting.
                </p>
            </div>
        @endif

        {{-- ── Version History ── --}}
        {{-- Always shown below the Rate / nudge block --}}
        <div class="border border-gray-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Version History</h2>
            <div class="space-y-3">
                {{-- Most recent version first; single-version case renders correctly as-is --}}
                @foreach ($versions->sortByDesc('created_at') as $version)
                    <div class="flex items-start space-x-3 {{ $version->id === $lessonPlan->id ? 'bg-gray-50 -mx-2 px-2 py-1.5 rounded-md' : '' }}">
                        <div class="flex-shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono font-bold text-gray-600 whitespace-nowrap">
                            {{ $version->semantic_version }}
                        </div>
                        <div class="min-w-0 flex-1">
                            @if ($version->id === $lessonPlan->id)
                                <span class="text-sm font-medium text-gray-900">{{ $version->class_name }} Day {{ $version->lesson_day }}</span>
                                <span class="text-xs text-gray-500 block">Current</span>
                            @else
                                <a href="{{ route('lesson-plans.show', $version) }}"
                                   class="text-sm text-gray-900 hover:text-gray-600 font-medium underline">
                                    {{ $version->class_name }} Day {{ $version->lesson_day }}
                                </a>
                            @endif
                            <p class="text-xs text-gray-500">
                                by {{ $version->author->name ?? 'No Teacher Name' }}
                                &middot; {{ $version->created_at->format('M j, Y') }}
                                &middot; <span class="{{ $version->vote_score > 0 ? 'text-green-600' : ($version->vote_score < 0 ? 'text-red-600' : '') }}">{{ $version->vote_score > 0 ? '+' : '' }}{{ $version->vote_score }}</span>
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</x-layout>
