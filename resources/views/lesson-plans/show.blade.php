<x-layout>
    <x-slot:title>{{ $lessonPlan->name }} — ARES Education</x-slot>

    <div class="max-w-4xl mx-auto">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-6 gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $lessonPlan->class_name }} — Day {{ $lessonPlan->lesson_day }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Version {{ $lessonPlan->version_number }}
                    &middot; by {{ $lessonPlan->author->name ?? 'Unknown' }}
                    &middot; {{ $lessonPlan->created_at->format('M j, Y g:ia') }} UTC
                </p>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $lessonPlan->name }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Main Content (left 2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Plan Details --}}
                <div class="border border-gray-200 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-3">Plan Details</h2>

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
                            <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->version_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Author:</span>
                            <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->author->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">File:</span>
                            <span class="text-gray-900 font-medium ml-1">
                                {{ $lessonPlan->file_name ?? '—' }}
                                ({{ $lessonPlan->file_size_formatted }})
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Uploaded:</span>
                            <span class="text-gray-900 font-medium ml-1">{{ $lessonPlan->created_at->format('M j, Y g:ia') }} UTC</span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap items-center gap-3 mt-6 pt-4 border-t border-gray-100">
                        @if ($lessonPlan->file_path)
                            <a href="{{ route('lesson-plans.preview', $lessonPlan) }}"
                               class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                                Preview File
                            </a>
                            <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                               class="px-4 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                                Download File
                            </a>
                        @endif
                        @auth
                            <a href="{{ route('lesson-plans.new-version', $lessonPlan) }}"
                               class="px-4 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                                Create New Version
                            </a>
                        @endauth
                        @auth
                            @if ($lessonPlan->author_id === auth()->id())
                                <form method="POST" action="{{ route('lesson-plans.destroy', $lessonPlan) }}"
                                      onsubmit="return confirm('Are you sure you want to delete this version?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-md hover:bg-red-100 transition-colors">
                                        Delete
                                    </button>
                                </form>
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
                        @foreach ($versions as $version)
                            <div class="flex items-start space-x-3 {{ $version->id === $lessonPlan->id ? 'bg-gray-50 -mx-2 px-2 py-1.5 rounded-md' : '' }}">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                                    v{{ $version->version_number }}
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
