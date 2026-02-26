{{--
    Upvote/Downvote component. Four display modes:

    1. Readonly (guests / display-only):
        <x-vote-buttons :score="$plan->vote_score" :readonly="true" />

    2. Locked (auth user who hasn't viewed the plan yet, or is the author):
        <x-vote-buttons :score="$plan->vote_score" :locked="true" />

    3. Inline/AJAX (auth user who has viewed the plan — dashboard table):
        <x-vote-buttons :plan-id="$plan->id" :score="$plan->vote_score"
                        :user-vote="$planUserVote" :inline="true" />

    4. Interactive form (show page — full upvote/downvote UI):
        <x-vote-buttons :plan-id="$plan->id" :score="$plan->vote_score" :user-vote="$userVote" />
--}}
@props([
    'planId'   => null,
    'score'    => 0,
    'userVote' => null,
    'readonly' => false,
    'locked'   => false,
    'inline'   => false,
])

@if($readonly)
    {{-- Compact display for guests: no action possible --}}
    <span class="inline-flex items-center gap-1.5 text-xs whitespace-nowrap">
        <span class="text-gray-400">Vote 👍 👎</span>
        <span class="font-semibold {{ $score > 0 ? 'text-green-600' : ($score < 0 ? 'text-red-600' : 'text-gray-400') }}">
            {{ $score > 0 ? '+' : '' }}{{ $score }}
        </span>
    </span>

@elseif($locked)
    {{-- Greyed arrows: auth user who hasn't viewed the plan yet (or is the author) --}}
    <span class="inline-flex items-center gap-1 text-xs whitespace-nowrap"
          title="View this plan first to unlock voting">
        <span class="px-0.5 text-gray-300 select-none">▲</span>
        <span class="font-semibold min-w-[2rem] text-center
              {{ $score > 0 ? 'text-green-600' : ($score < 0 ? 'text-red-600' : 'text-gray-400') }}">
            {{ $score > 0 ? '+' : '' }}{{ $score }}
        </span>
        <span class="px-0.5 text-gray-300 select-none">▼</span>
    </span>

@elseif($inline)
    {{-- AJAX vote buttons for the dashboard table — no page reload --}}
    <div x-data="{
            score: {{ (int) $score }},
            userVote: {{ $userVote !== null ? (int) $userVote : 'null' }},
            vote(value) {
                fetch('{{ route('votes.store', $planId) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ value: value })
                }).then(r => r.json()).then(d => {
                    this.score = d.score;
                    this.userVote = d.userVote;
                });
            }
         }"
         class="inline-flex items-center gap-1 text-xs whitespace-nowrap">
        <button @click="vote(1)" type="button" title="Upvote"
                :class="userVote === 1 ? 'text-green-600 font-bold' : 'text-gray-400 hover:text-green-600'"
                class="px-0.5 rounded transition">▲</button>
        <span :class="score > 0 ? 'text-green-600' : (score < 0 ? 'text-red-600' : 'text-gray-400')"
              class="font-semibold min-w-[2rem] text-center"
              x-text="(score > 0 ? '+' : '') + score"></span>
        <button @click="vote(-1)" type="button" title="Downvote"
                :class="userVote === -1 ? 'text-red-600 font-bold' : 'text-gray-400 hover:text-red-600'"
                class="px-0.5 rounded transition">▼</button>
    </div>

@else
    {{-- Interactive upvote/downvote buttons (show page — form-based) --}}
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
