<x-layout>
    <x-slot:title>ARES: Lesson Plans</x-slot>

    {{-- ── Dashboard Counters + Favorite ── --}}
    <div class="mb-8 border border-gray-200 rounded-lg p-4 sm:p-5">
        <div class="flex flex-wrap gap-6 items-start">

            {{-- Counter: Unique Classes --}}
            <div class="text-center px-4">
                <p class="text-3xl font-bold text-gray-900">{{ $uniqueClassCount }}</p>
                <p class="text-xs text-gray-500 mt-1">Unique {{ Str::plural('Class', $uniqueClassCount) }}</p>
            </div>

            {{-- Counter: Total Lesson Plans --}}
            <div class="text-center px-4">
                <p class="text-3xl font-bold text-gray-900">{{ $totalPlanCount }}</p>
                <p class="text-xs text-gray-500 mt-1">Total Lesson {{ Str::plural('Plan', $totalPlanCount) }}</p>
            </div>

            {{-- Divider --}}
            <div class="hidden sm:block w-px h-14 bg-gray-200"></div>

            {{-- Favorite Lesson Plan --}}
            <div class="flex-1 min-w-[200px]">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Favorite Lesson Plan</p>
                @if ($favoritePlan && $favoritePlan->vote_score > 0)
                    <a href="{{ route('lesson-plans.show', $favoritePlan) }}"
                       class="text-sm font-medium text-gray-900 hover:text-gray-600 underline underline-offset-2">
                        {{ $favoritePlan->name }}
                    </a>
                    <p class="text-xs text-gray-500 mt-0.5">
                        by {{ $favoritePlan->author->name ?? 'Unknown' }}
                        &middot;
                        <span class="text-green-600 font-medium">+{{ $favoritePlan->vote_score }}</span> rating
                    </p>
                @else
                    <p class="text-sm text-gray-400 italic">No votes yet</p>
                @endif
            </div>

        </div>
    </div>

    {{-- Search & Filter Bar --}}
    <form method="GET" action="{{ route('dashboard') }}" class="mb-8 border border-gray-200 rounded-lg p-4 sm:p-5">
        <div class="flex flex-wrap gap-3 items-end">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       placeholder="Class name, document name, or author..."
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
            </div>

            {{-- Class Name Filter --}}
            <div class="w-48">
                <label for="class_name" class="block text-xs font-medium text-gray-500 mb-1">Class</label>
                <select name="class_name" id="class_name"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                    <option value="">All Classes</option>
                    @foreach ($classNames as $cn)
                        <option value="{{ $cn }}" {{ request('class_name') === $cn ? 'selected' : '' }}>{{ $cn }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Latest Version Filter (default: show all; check to restrict) --}}
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="latest_only" id="latest_only" value="1"
                       {{ request('latest_only') ? 'checked' : '' }}
                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                <label for="latest_only" class="text-sm text-gray-600">Latest version only</label>
            </div>

            {{-- Buttons --}}
            <button type="submit"
                    class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                Search
            </button>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900">Clear</a>
        </div>
    </form>

    {{-- Upload button (visible to signed-in users) --}}
    @auth
        <div class="mb-6 flex justify-end">
            <a href="{{ route('lesson-plans.create') }}"
               class="inline-flex items-center px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                + Upload New Lesson Plan
            </a>
        </div>
    @endauth

    {{-- Results Table --}}
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        @php
                            // align: controls th text-align and the flex justification of the sort link
                            $cols = [
                                'class_name'     => ['label' => 'Class',   'align' => 'left'],
                                'lesson_day'     => ['label' => 'Day #',   'align' => 'center'],
                                'author_name'    => ['label' => 'Author',  'align' => 'left'],
                                'version_number' => ['label' => 'Version', 'align' => 'center'],
                                'vote_score'     => ['label' => 'Rating',  'align' => 'center'],
                                'updated_at'     => ['label' => 'Updated', 'align' => 'left'],
                            ];
                        @endphp
                        @foreach ($cols as $field => $col)
                            @php
                                $isActive  = ($sortField === $field);
                                $nextOrder = ($isActive && $sortOrder === 'asc') ? 'desc' : 'asc';
                                $thAlign   = $col['align'] === 'center' ? 'text-center' : 'text-left';
                                $linkAlign = $col['align'] === 'center' ? 'justify-center w-full' : '';
                            @endphp
                            <th class="px-4 py-3 {{ $thAlign }} text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <a href="{{ route('dashboard', array_merge(request()->query(), ['sort' => $field, 'order' => $nextOrder])) }}"
                                   class="inline-flex items-center {{ $linkAlign }} hover:text-gray-900 {{ $isActive ? 'text-gray-900' : '' }}">
                                    {{ $col['label'] }}
                                    @if ($isActive)
                                        <span class="ml-1">{!! $sortOrder === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                    @endif
                                </a>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($plans as $plan)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $plan->class_name }}</td>
                            <td class="px-4 py-3 text-gray-700 text-center">{{ $plan->lesson_day }}</td>
                            {{-- Author: email with @ and . stripped per spec Section 2.1 --}}
                            <td class="px-4 py-3 text-gray-700 text-xs">{{ str_replace(['.', '@'], '', $plan->author_name ?? '—') }}</td>
                            <td class="px-4 py-3 text-gray-700 text-center">{{ $plan->version_number }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    // Voting is unlocked when: logged in + verified + not the author + has viewed the plan
                                    $canVote = Auth::check()
                                        && Auth::user()->hasVerifiedEmail()
                                        && $plan->author_id !== Auth::id()
                                        && in_array($plan->id, $viewedIds);
                                    $planUserVote = $userVotes[$plan->id] ?? null;
                                @endphp
                                @auth
                                    @if($canVote)
                                        {{-- Active AJAX vote buttons --}}
                                        <x-vote-buttons :plan-id="$plan->id" :score="$plan->vote_score"
                                                        :user-vote="$planUserVote" :inline="true" />
                                    @else
                                        {{-- Greyed locked buttons (not viewed yet, or is author) --}}
                                        <x-vote-buttons :score="$plan->vote_score" :locked="true" />
                                    @endif
                                @else
                                    {{-- Guest: display only --}}
                                    <x-vote-buttons :score="$plan->vote_score" :readonly="true" />
                                @endauth
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $plan->updated_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @auth
                                    <a href="{{ route('lesson-plans.show', $plan) }}"
                                       class="inline-block px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                                        View/Edit
                                    </a>
                                @else
                                    {{-- Greyed out for guests — sign in required to view plans --}}
                                    <span class="inline-block px-3 py-1 text-xs font-medium text-gray-400 bg-gray-100 rounded-md cursor-not-allowed"
                                          title="Sign in to view lesson plans">
                                        View/Edit
                                    </span>
                                @endauth
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No lesson plans found. {{ request('search') ? 'Try a different search.' : '' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($plans->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $plans->links() }}
            </div>
        @endif
    </div>

    {{-- Summary --}}
    <div class="mt-3 text-xs text-gray-400">
        Showing {{ $plans->firstItem() ?? 0 }}–{{ $plans->lastItem() ?? 0 }} of {{ $plans->total() }} plans
    </div>

</x-layout>
