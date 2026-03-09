<x-layout>
    <x-slot:title>{{ $lessonPlan->class_name }} Grade {{ $lessonPlan->grade }} Lesson {{ $lessonPlan->lesson_day }} — ARES Education</x-slot>

    @php
        // Pre-compute viewer URLs (server-generated, not user input)
        $googleViewUrl = $lessonPlan->file_path
            ? 'https://docs.google.com/gview?url=' . urlencode(asset('storage/' . $lessonPlan->file_path))
            : '';
        $officeViewUrl = $lessonPlan->file_path
            ? 'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode(asset('storage/' . $lessonPlan->file_path))
            : '';

        // Sort once; reuse for $latestVersion and the version history loop
        $sortedVersions  = $versions->sortByDesc('created_at');
        $latestVersion   = $sortedVersions->first();
        $originalVersion = $versions->first(fn($v) => $v->original_id === null)
                        ?? $sortedVersions->last();
    @endphp

    {{--
        Single top-level x-data component so all state (details visibility,
        viewer preference, favorites, voting, delete confirm) is shared across
        sections without nested components or window events.
    --}}
    <div class="max-w-4xl mx-auto space-y-6"
         x-data="{
             showDetails:   false,
             useGoogleDocs: false,
             confirmOpen:   false,
             favorited:     {{ $isFavorited ? 'true' : 'false' }},
             favLoading:    false,
             engaged:       {{ $hasEngaged ? 'true' : 'false' }},
             score:         {{ (int) $lessonPlan->vote_score }},
             userVote:      {{ $userVote ? (int) $userVote->value : 'null' }},
             voteLoading:   false,
             googleUrl:     '{{ $googleViewUrl }}',
             officeUrl:     '{{ $officeViewUrl }}',

             toggleFavorite() {
                 if (this.favLoading) return;
                 this.favLoading = true;
                 fetch('{{ route('favorites.toggle', $lessonPlan) }}', {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         'Accept': 'application/json'
                     }
                 })
                 .then(r => r.ok ? r.json() : null)
                 .then(d => { if (d !== null) this.favorited = d.favorited; })
                 .catch(() => {})
                 .finally(() => { this.favLoading = false; });
             },

             openViewer() {
                 const url  = this.useGoogleDocs ? this.googleUrl : this.officeUrl;
                 const type = this.useGoogleDocs ? 'google_docs' : 'ms_office';
                 fetch('{{ route('lesson-plans.track-engagement', $lessonPlan) }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         'Accept': 'application/json'
                     },
                     body: JSON.stringify({ type: type })
                 }).then(r => { if (r.ok) this.engaged = true; }).catch(() => {});
                 window.open(url, '_blank', 'noopener,noreferrer');
             },

             castVote(value) {
                 if (this.voteLoading) return;
                 this.voteLoading = true;
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
                 .finally(() => { this.voteLoading = false; });
             },

             resetVote() {
                 if (this.voteLoading || this.userVote === null) return;
                 this.voteLoading = true;
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
                 .finally(() => { this.voteLoading = false; });
             }
         }">

        {{-- Page header: dynamic class info --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $lessonPlan->class_name }}, Grade {{ $lessonPlan->grade }}, Lesson {{ $lessonPlan->lesson_day }}
            </h1>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors shrink-0">
                &larr; Back to Dashboard
            </a>
        </div>

        {{-- Show Details checkbox --}}
        <label class="inline-flex items-center gap-2 cursor-pointer select-none text-sm text-gray-700">
            <input type="checkbox" x-model="showDetails"
                   class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900">
            Show Details
        </label>

        {{-- Details Box (conditional on checkbox) --}}
        <div x-show="showDetails" x-cloak class="border border-gray-200 rounded-lg p-6 space-y-4">

            {{-- 1. Description --}}
            @if ($lessonPlan->description)
                <p class="text-gray-900 font-semibold text-sm">{{ $lessonPlan->description }}</p>
            @else
                <p class="text-gray-400 text-sm italic font-semibold">No description provided.</p>
            @endif

            {{-- Canonical filename --}}
            @if ($lessonPlan->file_name)
                <p class="text-xs text-gray-400 font-mono break-all">{{ $lessonPlan->file_name }}</p>
            @endif

            {{-- 2. Meta string --}}
            <p class="text-sm text-gray-700">
                {{ $lessonPlan->class_name }}, Grade {{ $lessonPlan->grade }},
                Lesson {{ $lessonPlan->lesson_day }}, Version {{ $lessonPlan->semantic_version }}
            </p>

            {{-- 3. Favorites (reactive display; toggle button shown for authors) --}}
            <div class="flex items-center gap-3 text-sm text-gray-700">
                <span>Is one of your favorites:
                    <span class="font-medium" x-text="favorited ? 'Yes' : 'No'"></span>
                </span>
                @if ($isAuthorOfPlan)
                    <button @click="toggleFavorite()" type="button" :disabled="favLoading"
                            :class="favorited
                                ? 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'
                                : 'bg-yellow-50 border-yellow-300 text-yellow-700 hover:bg-yellow-100'"
                            class="px-2.5 py-1 border rounded text-xs font-medium transition-colors disabled:opacity-60"
                            x-text="favorited ? 'Unfavorite' : 'Favorite'">
                    </button>
                @endif
            </div>

            {{-- 4. Original Credits --}}
            @if ($originalVersion)
                <p class="text-sm text-gray-700">
                    Original Contributor: <span class="font-medium">{{ $originalVersion->author->name ?? 'Anonymous' }}</span>,
                    Upload Date: <span class="font-medium">{{ $originalVersion->created_at->format('M j, Y') }}</span>,
                    Rating:
                    <span class="font-medium {{ $originalVersion->vote_score > 0 ? 'text-green-600' : ($originalVersion->vote_score < 0 ? 'text-red-600' : 'text-gray-500') }}">
                        {{ $originalVersion->vote_score > 0 ? '+' . $originalVersion->vote_score : $originalVersion->vote_score }}
                    </span>
                </p>
            @endif

            {{-- 5. Most Recent Credits (only when different from original) --}}
            @if ($latestVersion && $latestVersion->id !== ($originalVersion?->id))
                <p class="text-sm text-gray-700">
                    Most Recent Contributor: <span class="font-medium">{{ $latestVersion->author->name ?? 'Anonymous' }}</span>,
                    Upload Date: <span class="font-medium">{{ $latestVersion->created_at->format('M j, Y') }}</span>,
                    Rating:
                    <span class="font-medium {{ $latestVersion->vote_score > 0 ? 'text-green-600' : ($latestVersion->vote_score < 0 ? 'text-red-600' : 'text-gray-500') }}">
                        {{ $latestVersion->vote_score > 0 ? '+' . $latestVersion->vote_score : $latestVersion->vote_score }}
                    </span>
                </p>
            @endif

            {{-- 6. Version History (most recent first, alternating row backgrounds) --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Version History</p>
                <div class="rounded overflow-hidden border border-gray-100">
                    @foreach ($sortedVersions as $version)
                        <div class="py-1.5 px-3 text-sm {{ $loop->even ? 'bg-gray-50' : 'bg-white' }} {{ $version->id === $lessonPlan->id ? 'font-semibold text-gray-900' : 'text-gray-700' }}">
                            @if ($version->id === $lessonPlan->id)
                                v{{ $version->semantic_version }} uploaded {{ $version->created_at->format('M j, Y') }} by {{ $version->author->name ?? 'Anonymous' }}
                                <span class="text-xs font-normal text-gray-400 ml-1">(current)</span>
                            @else
                                <a href="{{ route('lesson-plans.show', $version) }}" class="hover:underline">
                                    v{{ $version->semantic_version }} uploaded {{ $version->created_at->format('M j, Y') }} by {{ $version->author->name ?? 'Anonymous' }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Document Viewer and Actions --}}
        <div class="border border-gray-200 rounded-lg p-6 space-y-4">

            @if ($lessonPlan->file_path)
                {{-- "I use" viewer preference toggle --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-gray-700">I use:</span>
                    <div class="inline-flex rounded-md border border-gray-300 overflow-hidden">
                        <button type="button" @click="useGoogleDocs = false"
                                :class="!useGoogleDocs ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-3 py-1.5 text-sm font-medium transition-colors">
                            Microsoft Office
                        </button>
                        <button type="button" @click="useGoogleDocs = true"
                                :class="useGoogleDocs ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-3 py-1.5 text-sm font-medium border-l border-gray-300 transition-colors">
                            Google Docs
                        </button>
                    </div>
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="flex flex-col sm:flex-row flex-wrap gap-2">

                @if ($lessonPlan->file_path)
                    {{-- Unified viewer button --}}
                    <button type="button" @click="openViewer()"
                            class="flex flex-col items-center justify-center min-h-[3.5rem] flex-1 px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                        <span>View/Download This Plan ↗</span>
                        <span class="text-xs font-normal opacity-75"
                              x-text="useGoogleDocs ? '(Google Docs)' : '(Microsoft Office)'"></span>
                    </button>

                    {{-- Download --}}
                    <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                       @click="fetch('{{ route('lesson-plans.track-engagement', $lessonPlan) }}', {
                           method: 'POST',
                           headers: {
                               'Content-Type': 'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                               'Accept': 'application/json'
                           },
                           body: JSON.stringify({ type: 'download' })
                       }).then(r => { if (r.ok) engaged = true; }).catch(() => {})"
                       class="flex flex-col items-center justify-center min-h-[3.5rem] flex-1 px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300 text-center">
                        <span>Download This Lesson Plan</span>
                        <span class="text-xs font-normal text-gray-500">(DOC/DOCX)</span>
                    </a>
                @endif

                {{-- Upload Revision --}}
                <a href="{{ route('lesson-plans.new-version', $lessonPlan) }}"
                   class="flex flex-col items-center justify-center min-h-[3.5rem] flex-1 px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                    <span>Upload Your Revision</span>
                    <span class="text-xs font-normal text-gray-500">Of This Lesson Plan</span>
                </a>

                @if ($isAuthorOfPlan)
                    <button type="button" @click="confirmOpen = true"
                            class="flex flex-col items-center justify-center min-h-[3.5rem] flex-1 px-3 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-md hover:bg-red-100 transition-colors border border-red-200">
                        <span>Delete Your Version</span>
                        <span class="text-xs font-normal">This Cannot Be Undone!</span>
                    </button>
                @endif

            </div>

            {{-- Delete confirmation modal --}}
            @if ($isAuthorOfPlan)
                <div x-show="confirmOpen" x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6"
                         @click.away="confirmOpen = false">
                        <p class="text-gray-900 font-medium text-center mb-6">
                            Are you sure? This action cannot be undone
                        </p>
                        <div class="flex gap-3">
                            <button type="button" @click="confirmOpen = false"
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
            @endif

        </div>

        {{-- Rate This Document --}}
        {{-- Authors cannot vote on their own plans (B1 server-side block). --}}
        {{-- Non-authors: engagement-gated voting. --}}
        @if (!$isAuthorOfPlan)
            <div class="border border-gray-200 rounded-lg">

                {{-- Nudge: shown until the user opens or downloads the plan --}}
                <div x-show="!engaged" class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-500 text-center">
                        Open this plan in an external viewer or download it to unlock voting.
                    </p>
                </div>

                {{-- Voting buttons: revealed once engagement fires --}}
                <div x-show="engaged" class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Rate This Document</h2>

                    <div class="flex items-center gap-3 mb-5">
                        <span class="text-3xl font-bold tabular-nums"
                              :class="score > 0 ? 'text-green-600' : (score < 0 ? 'text-red-600' : 'text-gray-400')"
                              x-text="(score > 0 ? '+' : '') + score"></span>
                        <span class="text-sm text-gray-500">community rating</span>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <button @click="castVote(1)" type="button" :disabled="voteLoading"
                                :class="userVote === 1
                                    ? 'bg-green-100 border-green-400 text-green-700'
                                    : 'bg-white border-gray-300 text-gray-700 hover:bg-green-50 hover:border-green-300'"
                                class="flex-1 px-4 py-2.5 border rounded-md text-sm font-medium transition-colors disabled:opacity-60">
                            Upvote This Version
                        </button>
                        <button @click="castVote(-1)" type="button" :disabled="voteLoading"
                                :class="userVote === -1
                                    ? 'bg-red-100 border-red-400 text-red-700'
                                    : 'bg-white border-gray-300 text-gray-700 hover:bg-red-50 hover:border-red-300'"
                                class="flex-1 px-4 py-2.5 border rounded-md text-sm font-medium transition-colors disabled:opacity-60">
                            Downvote This Version
                        </button>
                        <button @click="resetVote()" type="button"
                                :disabled="userVote === null || voteLoading"
                                :class="userVote !== null
                                    ? 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                    : 'bg-gray-50 border-gray-200 text-gray-300 cursor-not-allowed'"
                                class="flex-1 px-4 py-2.5 border rounded-md text-sm font-medium transition-colors disabled:opacity-60">
                            Reset Vote
                        </button>
                    </div>

                    <p x-show="userVote !== null" x-cloak class="text-xs text-gray-400 mt-2">
                        Your vote is recorded. Click "Reset Vote" to remove it.
                    </p>
                </div>

            </div>
        @endif

    </div>

</x-layout>
