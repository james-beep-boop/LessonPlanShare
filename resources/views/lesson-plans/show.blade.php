<x-layout>
    <x-slot:title>{{ $lessonPlan->name }} — ARES Education</x-slot>

    <div class="max-w-4xl mx-auto">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-6 gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $lessonPlan->class_name }} — Day {{ $lessonPlan->lesson_day }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Version {{ $lessonPlan->semantic_version }}
                    &middot; by {{ $lessonPlan->author->name ?? 'Unknown' }}
                    &middot; {{ $lessonPlan->created_at->format('M j, Y g:ia') }} UTC
                </p>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $lessonPlan->name }}</p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors shrink-0">
                &larr; Back to Dashboard
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Main Content (left 2/3) --}}
            <div class="lg:col-span-2 space-y-6">

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
                            <span class="text-gray-500">Author:</span>
                            <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->author->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Uploaded:</span>
                            <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->created_at->format('M j, Y g:ia') }} UTC</span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-6 pt-4 border-t border-gray-100 space-y-2">

                        {{-- Row 1: Preview · Download · Print/PDF — stacks on mobile, 3-col on sm+ --}}
                        {{-- Print/PDF opens the raw file directly (bypasses Google Docs Viewer cross-origin iframe). --}}
                        {{-- PDF: browser native viewer → Ctrl+P / ⌘+P prints the document. DOCX/other: downloads. --}}
                        {{-- window.print() is intentionally NOT used — it would print the app UI, not the document. --}}
                        @if ($lessonPlan->file_path)
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <a href="{{ route('lesson-plans.preview', $lessonPlan) }}"
                                   class="text-center px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                                    Preview
                                </a>
                                <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                                   class="text-center px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                                    Download
                                </a>
                                <a href="{{ asset('storage/' . $lessonPlan->file_path) }}"
                                   target="_blank" rel="noopener"
                                   title="Opens raw document — Ctrl+P / ⌘+P to print (PDF opens in browser; other formats download)"
                                   class="text-center px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                                    Print / PDF
                                </a>
                            </div>
                        @endif

                        {{-- Row 2: New Version + Delete (author only) — equal-width 2-col grid --}}
                        @auth
                            @if ($lessonPlan->author_id === auth()->id())
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="{{ route('lesson-plans.new-version', $lessonPlan) }}"
                                       class="text-center px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                                        New Version
                                    </a>

                                    {{-- Delete: Alpine modal gives exact "Yes, Delete" CTA (native confirm() cannot do this) --}}
                                    <div x-data="{ confirmOpen: false }">
                                        <button type="button"
                                                @click="confirmOpen = true"
                                                class="w-full text-center px-3 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-md hover:bg-red-100 transition-colors border border-red-200">
                                            Delete
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
                                </div>
                            @else
                                <a href="{{ route('lesson-plans.new-version', $lessonPlan) }}"
                                   class="block w-full text-center px-3 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                                    Create New Version
                                </a>
                            @endif
                        @endauth

                    </div>
                </div>

                {{-- Voting Section --}}
                <div class="border border-gray-200 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-3">Community Rating</h2>

                    {{-- Vote Score Display --}}
                    <div class="flex items-center space-x-4 mb-4">
                        <span class="text-3xl font-bold {{ $lessonPlan->vote_score > 0 ? 'text-green-600' : ($lessonPlan->vote_score < 0 ? 'text-red-600' : 'text-gray-400') }}">
                            {{ $lessonPlan->vote_score > 0 ? '+' : '' }}{{ $lessonPlan->vote_score }}
                        </span>
                        <span class="text-sm text-gray-500">
                            {{ $lessonPlan->upvote_count }} {{ Str::plural('upvote', $lessonPlan->upvote_count) }},
                            {{ $lessonPlan->downvote_count }} {{ Str::plural('downvote', $lessonPlan->downvote_count) }}
                        </span>
                    </div>

                    {{-- Vote Buttons (if logged in and not the author) --}}
                    @auth
                        @if ($lessonPlan->author_id !== auth()->id())
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">Cast your vote:</p>
                                <x-vote-buttons :plan-id="$lessonPlan->id" :score="$lessonPlan->vote_score" :user-vote="$userVote" />
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic border-t border-gray-100 pt-4">
                                You cannot vote on your own lesson plan.
                            </p>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 border-t border-gray-100 pt-4">
                            <button x-data @click="$dispatch('open-auth-modal', { mode: 'login' })"
                                    class="text-gray-900 hover:text-gray-600 underline cursor-pointer">Sign in</button>
                            to vote on this plan.
                        </p>
                    @endauth
                </div>
            </div>

            {{-- Sidebar: Version History (right 1/3) --}}
            <div class="space-y-6">
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
                                        by {{ $version->author->name ?? '—' }}
                                        &middot; {{ $version->created_at->format('M j, Y') }}
                                        &middot; <span class="{{ $version->vote_score > 0 ? 'text-green-600' : ($version->vote_score < 0 ? 'text-red-600' : '') }}">{{ $version->vote_score > 0 ? '+' : '' }}{{ $version->vote_score }}</span>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

</x-layout>
