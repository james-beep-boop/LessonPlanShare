<x-layout>
    <x-slot:title>{{ $lessonPlan->class_name }} Grade {{ $lessonPlan->grade }} Lesson {{ $lessonPlan->lesson_day }} — ARES Education</x-slot>

    @php
        // Pre-compute viewer URLs using the controller-supplied signed URL.
        // The signed URL points to the private-disk serve route (4-hour expiry).
        // External viewer services (Google Docs, Office Online) fetch the file via
        // a server-to-server request — they cannot use session cookies, but the
        // signed URL acts as a short-lived token that authorises the single fetch.
        $googleViewUrl = $viewerUrl
            ? 'https://docs.google.com/gview?url=' . urlencode($viewerUrl)
            : '';
        $officeViewUrl = $viewerUrl
            ? 'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($viewerUrl)
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
             showCompare:   {{ $targetPlan ? 'true' : 'false' }},
             sideBySide:    false,
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

        {{-- Description (always visible, between header and checkbox) --}}
        @if ($lessonPlan->description)
            <p class="text-gray-900 font-semibold text-sm">{{ $lessonPlan->description }}</p>
        @else
            <p class="text-gray-400 text-sm italic font-semibold">No description provided.</p>
        @endif

        {{-- Show Details checkbox --}}
        <label class="inline-flex items-center gap-2 cursor-pointer select-none text-sm text-gray-700">
            <input type="checkbox" x-model="showDetails"
                   class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900">
            Show Details of this Lesson Plan
        </label>

        {{-- Details Box (conditional on checkbox) --}}
        <div x-show="showDetails" x-cloak class="border border-gray-200 rounded-lg p-6 space-y-4">

            {{-- Canonical filename --}}
            @if ($lessonPlan->file_name)
                <p class="text-xs text-gray-400 font-mono break-all">{{ $lessonPlan->file_name }}</p>
            @endif

            {{-- 2. Meta string --}}
            <p class="text-sm text-gray-700">
                {{ $lessonPlan->class_name }}, Grade {{ $lessonPlan->grade }},
                Lesson {{ $lessonPlan->lesson_day }}, Version {{ $lessonPlan->semantic_version }}
            </p>

            {{-- 3. Rating tally + three vote buttons (Upvote / Neutral / Downvote) --}}
            {{-- "Your vote" shows +1/-1/— (em-dash = no vote yet, not zero).        --}}
            {{-- Already-voted button is natively disabled (accessibility) + styled   --}}
            {{-- with the opposite pale colour to signal the locked state visually.   --}}
            {{-- Neutral is disabled when userVote is null (nothing to clear).        --}}
            <div class="space-y-2 text-sm text-gray-700">
                <div class="flex items-center gap-2 flex-wrap">
                    <span>Rating:
                        <span class="font-bold tabular-nums"
                              :class="score > 0 ? 'text-green-600' : (score < 0 ? 'text-red-600' : 'text-gray-500')"
                              x-text="(score > 0 ? '+' : '') + score"></span>.
                    </span>
                    <span>Your vote:
                        <span class="font-bold tabular-nums"
                              :class="userVote === 1 ? 'text-green-600' : (userVote === -1 ? 'text-red-600' : 'text-gray-400')"
                              x-text="userVote === 1 ? '+1' : (userVote === -1 ? '-1' : '—')"></span>.
                    </span>
                </div>
                @if (!$isAuthorOfPlan)
                    <div x-show="engaged" x-cloak class="flex gap-1.5">
                        {{-- Upvote: bold green when available; pale red + disabled when already upvoted --}}
                        <button type="button"
                                @click="castVote(1)"
                                :disabled="userVote === 1 || voteLoading"
                                :class="userVote === 1
                                    ? 'bg-red-50 border-red-200 text-red-300 cursor-not-allowed'
                                    : 'bg-green-600 border-green-700 text-white hover:bg-green-700 font-bold'"
                                class="px-3 py-1 border rounded text-xs transition-colors">
                            Upvote
                        </button>
                        {{-- Neutral: clears vote; disabled when there is no vote to clear --}}
                        <button type="button"
                                @click="resetVote()"
                                :disabled="userVote === null || voteLoading"
                                :class="userVote === null
                                    ? 'bg-gray-200 border-gray-300 text-gray-400 cursor-not-allowed'
                                    : 'bg-gray-100 border-gray-300 text-gray-600 hover:bg-gray-200'"
                                class="px-3 py-1 border rounded text-xs transition-colors">
                            Neutral
                        </button>
                        {{-- Downvote: bold red when available; pale green + disabled when already downvoted --}}
                        <button type="button"
                                @click="castVote(-1)"
                                :disabled="userVote === -1 || voteLoading"
                                :class="userVote === -1
                                    ? 'bg-green-50 border-green-200 text-green-300 cursor-not-allowed'
                                    : 'bg-red-600 border-red-700 text-white hover:bg-red-700 font-bold'"
                                class="px-3 py-1 border rounded text-xs transition-colors">
                            Downvote
                        </button>
                    </div>
                @endif
            </div>

            {{-- 4. Favorites status + Change button --}}
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <span class="font-medium">Your Favorite?</span>
                {{-- Star: yellow ★ when favorited, white/outline ☆ when not --}}
                <span class="text-2xl leading-none select-none"
                      :class="favorited ? 'text-yellow-400' : 'text-gray-300'"
                      x-text="favorited ? '★' : '☆'"></span>
                <div x-show="engaged" x-cloak>
                    <button @click="toggleFavorite()" type="button" :disabled="favLoading"
                            class="px-2.5 py-1 border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 rounded text-xs font-medium transition-colors disabled:opacity-60">
                        Change
                    </button>
                </div>
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

            {{-- Viewer buttons + optional Compare button in one row --}}
            @if ($lessonPlan->file_path)
                <div class="flex flex-wrap justify-center gap-3">
                    <button type="button" @click="useGoogleDocs = false; openViewer()"
                            class="px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                        View with Microsoft Office
                    </button>
                    <button type="button" @click="useGoogleDocs = true; openViewer()"
                            class="px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                        View with Google Docs
                    </button>
                    @if ($versions->count() > 1)
                        <button type="button" @click="showCompare = !showCompare"
                                class="px-4 py-2.5 text-sm font-medium rounded-md transition-colors"
                                :class="showCompare
                                    ? 'bg-gray-600 text-white'
                                    : 'bg-gray-900 text-white hover:bg-gray-700'">
                            Compare Two Versions
                        </button>
                    @endif
                </div>
            @endif

            {{-- Action buttons --}}
            <div class="flex flex-col sm:flex-row flex-wrap gap-2">

                @if ($lessonPlan->file_path)
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

        {{-- Inline compare panel — toggled by the Compare button above --}}
        @if ($versions->count() > 1)
            <div x-show="showCompare" x-cloak class="space-y-4">

                {{-- Revision selector --}}
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <p class="text-sm font-semibold text-gray-900">Select a Revision to Compare Against</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Comparing from current: v{{ $lessonPlan->semantic_version }}.
                            Click a row to load the diff below.
                        </p>
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-2">Version</th>
                                <th class="px-4 py-2">Official</th>
                                <th class="px-4 py-2">Contributor</th>
                                <th class="px-4 py-2">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($versions->where('id', '!=', $lessonPlan->id) as $cv)
                                <tr class="border-t border-gray-100 cursor-pointer
                                           {{ $targetPlan && $targetPlan->id === $cv->id ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                                    onclick="window.location='{{ route('lesson-plans.show', ['lessonPlan' => $lessonPlan, 'compare_to' => $cv->id]) }}'">
                                    <td class="px-4 py-2 font-medium">
                                        v{{ $cv->semantic_version }}
                                        @if ($targetPlan && $targetPlan->id === $cv->id)
                                            <span class="ml-1 text-xs font-normal text-blue-600">(selected)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">{{ $cv->is_official ? 'Yes' : 'No' }}</td>
                                    <td class="px-4 py-2">{{ $cv->author->name ?? 'Anonymous' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $cv->created_at->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($diffWarning)
                    <div class="border border-amber-200 bg-amber-50 rounded-lg p-4 text-sm text-amber-800">
                        {{ $diffWarning }}
                    </div>
                @endif

                @if (!empty($diffOps))

                    {{-- Summary stats --}}
                    @if ($diffSummary)
                        <div class="grid grid-cols-3 gap-3 text-center text-sm">
                            <div class="border border-green-200 bg-green-50 rounded-lg p-3">
                                <p class="text-xl font-bold text-green-700">+{{ $diffSummary['added'] }}</p>
                                <p class="text-xs text-green-700 uppercase tracking-wider mt-0.5">Lines Added</p>
                            </div>
                            <div class="border border-red-200 bg-red-50 rounded-lg p-3">
                                <p class="text-xl font-bold text-red-700">-{{ $diffSummary['removed'] }}</p>
                                <p class="text-xs text-red-700 uppercase tracking-wider mt-0.5">Lines Removed</p>
                            </div>
                            <div class="border border-gray-200 bg-gray-50 rounded-lg p-3">
                                <p class="text-xl font-bold text-gray-700">{{ $diffSummary['changed'] }}</p>
                                <p class="text-xs text-gray-700 uppercase tracking-wider mt-0.5">Lines Changed</p>
                            </div>
                        </div>
                    @endif

                    {{-- View toggle --}}
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">View:</span>
                        <button type="button" @click="sideBySide = false"
                                :class="!sideBySide ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors">
                            Inline
                        </button>
                        <button type="button" @click="sideBySide = true"
                                :class="sideBySide ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors">
                            Side by Side
                        </button>
                    </div>

                    {{-- Inline diff --}}
                    <div x-show="!sideBySide" class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-xs text-gray-500">
                            Baseline: v{{ $targetPlan->semantic_version }} ({{ $targetPlan->author->name ?? 'Anonymous' }})
                            &rarr; Current: v{{ $lessonPlan->semantic_version }}
                        </div>
                        <div class="overflow-x-auto">
                            <div class="font-mono text-xs">
                                @foreach ($diffOps as $op)
                                    @php
                                        $prefix = $op['type'] === 'add' ? '+' : ($op['type'] === 'remove' ? '−' : ' ');
                                        $rowClass = $op['type'] === 'add'
                                            ? 'bg-green-50 text-green-900'
                                            : ($op['type'] === 'remove'
                                                ? 'bg-red-50 text-red-900'
                                                : 'text-gray-600');
                                    @endphp
                                    <div class="flex gap-3 px-4 py-0.5 {{ $rowClass }}">
                                        <span class="select-none w-4 shrink-0 font-bold">{{ $prefix }}</span>
                                        <span class="whitespace-pre-wrap break-all">{{ $op['line'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Side-by-side diff --}}
                    <div x-show="sideBySide" x-cloak class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-xs text-gray-500">
                            Left: v{{ $targetPlan->semantic_version }} (baseline)
                            &nbsp;|&nbsp;
                            Right: v{{ $lessonPlan->semantic_version }} (current)
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full font-mono text-xs border-collapse">
                                <tbody>
                                    @foreach ($sideBySide as $row)
                                        @php
                                            $leftClass = match($row['type']) {
                                                'remove', 'change' => 'bg-red-50 text-red-900',
                                                default => 'text-gray-600',
                                            };
                                            $rightClass = match($row['type']) {
                                                'add', 'change' => 'bg-green-50 text-green-900',
                                                default => 'text-gray-600',
                                            };
                                        @endphp
                                        <tr class="border-t border-gray-100">
                                            <td class="px-3 py-0.5 w-1/2 border-r border-gray-200 whitespace-pre-wrap break-all {{ $leftClass }}">{{ $row['left'] }}</td>
                                            <td class="px-3 py-0.5 w-1/2 whitespace-pre-wrap break-all {{ $rightClass }}">{{ $row['right'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                @elseif ($targetPlan && !$diffWarning)
                    <div class="border border-gray-200 bg-gray-50 rounded-lg p-4 text-sm text-gray-500 italic">
                        No differences found between the selected versions.
                    </div>
                @endif

            </div>
        @endif

    </div>

</x-layout>
