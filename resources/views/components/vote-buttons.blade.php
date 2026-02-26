{{--
    Upvote/Downvote component.

    Interactive (logged-in user):
        <x-vote-buttons :plan-id="$plan->id" :score="$plan->vote_score" :user-vote="$userVote" />

    Display-only (dashboard table):
        <x-vote-buttons :score="$plan->vote_score" :readonly="true" />
--}}
@props([
    'planId'   => null,
    'score'    => 0,
    'userVote' => null,
    'readonly' => false,
])

@if($readonly)
    {{-- Compact display for table rows (spec Section 14.3) --}}
    <span class="inline-flex items-center gap-1.5 text-xs whitespace-nowrap">
        <span class="text-gray-400">Vote 👍 👎</span>
        <span class="font-semibold {{ $score > 0 ? 'text-green-600' : ($score < 0 ? 'text-red-600' : 'text-gray-400') }}">
            {{ $score > 0 ? '+' : '' }}{{ $score }}
        </span>
    </span>

@else
    {{-- Interactive upvote/downvote buttons --}}
    @php
        $currentValue = $userVote ? $userVote->value : 0;
    @endphp
    <div class="inline-flex items-center space-x-2">
        {{-- Upvote --}}
        <form method="POST" action="{{ route('votes.store', $planId) }}" class="inline">
            @csrf
            <input type="hidden" name="value" value="1">
            <button type="submit" title="Upvote"
                    class="p-1.5 rounded-md transition {{ $currentValue === 1 ? 'bg-green-100 text-green-700' : 'text-gray-400 hover:text-green-600 hover:bg-green-50' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                </svg>
            </button>
        </form>

        {{-- Score --}}
        <span class="text-sm font-bold min-w-[2rem] text-center {{ $score > 0 ? 'text-green-600' : ($score < 0 ? 'text-red-600' : 'text-gray-500') }}">
            {{ $score > 0 ? '+' : '' }}{{ $score }}
        </span>

        {{-- Downvote --}}
        <form method="POST" action="{{ route('votes.store', $planId) }}" class="inline">
            @csrf
            <input type="hidden" name="value" value="-1">
            <button type="submit" title="Downvote"
                    class="p-1.5 rounded-md transition {{ $currentValue === -1 ? 'bg-red-100 text-red-700' : 'text-gray-400 hover:text-red-600 hover:bg-red-50' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </form>
    </div>

    @if ($currentValue !== 0)
        <p class="text-xs text-gray-400 mt-1">Click the same arrow again to remove your vote.</p>
    @endif
@endif
