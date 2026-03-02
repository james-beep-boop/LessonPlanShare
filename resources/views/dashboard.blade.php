<x-layout>
    <x-slot:title>ARES: Lesson Plans</x-slot>

    {{-- ── Dashboard Counters ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">

        {{-- Lesson Plans --}}
        <div class="border border-gray-200 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-gray-900">{{ $totalPlanCount }}</p>
            <p class="text-xs text-gray-500 mt-1">Lesson {{ Str::plural('Plan', $totalPlanCount) }}</p>
        </div>

        {{-- Contributors --}}
        <div class="border border-gray-200 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-gray-900">{{ $contributorCount }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ Str::plural('Contributor', $contributorCount) }}</p>
        </div>

        {{-- Top Rated Plan --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Top Rated</p>
            @if ($topRatedPlan)
                <a href="{{ route('lesson-plans.show', $topRatedPlan) }}"
                   class="text-sm font-medium text-gray-900 hover:text-gray-600 underline underline-offset-2 block truncate"
                   title="{{ $topRatedPlan->name }}">
                    {{ Str::limit($topRatedPlan->name, 20) }}
                </a>
                <p class="text-xs text-gray-500 mt-0.5">
                    <span class="text-green-600 font-medium">+{{ $topRatedPlan->vote_score }}</span> rating
                </p>
            @else
                <p class="text-sm text-gray-400 italic">No votes yet</p>
            @endif
        </div>

        {{-- Top Contributor --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Top Contributor</p>
            @if ($topContributor)
                <p class="text-sm font-medium text-gray-900 truncate">{{ ($topContributor->author->name ?? null) ?: 'No Teacher Name' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $topContributor->upload_count }} {{ Str::plural('plan', $topContributor->upload_count) }}</p>
            @else
                <p class="text-sm text-gray-400 italic">—</p>
            @endif
        </div>

    </div>

    {{-- Search & Filter Bar — class dropdown and free-text search only --}}
    <form method="GET" action="{{ route('dashboard') }}" class="mb-3 border border-gray-200 rounded-lg p-4 sm:p-5">
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

            {{-- Buttons --}}
            <button type="submit"
                    class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                Search
            </button>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900">Clear</a>
        </div>
    </form>

    {{-- Filter Utility Bar — sort hint + auto-submit checkboxes --}}
    <form method="GET" action="{{ route('dashboard') }}" id="filter-form" class="mb-6">
        {{-- Carry forward current search/class/sort params so checkboxes don't lose them --}}
        <input type="hidden" name="search"     value="{{ request('search') }}">
        <input type="hidden" name="class_name" value="{{ request('class_name') }}">
        <input type="hidden" name="sort"       value="{{ request('sort') }}">
        <input type="hidden" name="order"      value="{{ request('order') }}">

        <div class="flex flex-wrap items-center gap-5 px-1 text-sm text-gray-500">
            <span class="text-xs text-gray-400 italic">Click blue column heading to sort</span>

            {{-- Latest version filter --}}
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="latest_only" value="1"
                       {{ request('latest_only') ? 'checked' : '' }}
                       onchange="this.form.submit()"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                <span class="text-sm text-gray-600">Show Latest</span>
            </label>

            {{-- Favorites + My Plans filters — only shown to verified users; order: Faves, Mine --}}
            @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="favorites_only" value="1"
                           {{ request('favorites_only') ? 'checked' : '' }}
                           onchange="this.form.submit()"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                    <span class="text-sm text-gray-600">Show Faves</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="my_plans_only" value="1"
                           {{ request('my_plans_only') ? 'checked' : '' }}
                           onchange="this.form.submit()"
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                    <span class="text-sm text-gray-600">Show Mine</span>
                </label>
            @endif
        </div>
    </form>

    {{-- Results Table --}}
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                    @php
                        // Build column definitions once; used by both thead and tbody.
                        // Contributor column is hidden for guests as a sign-in incentive.
                        // Favorites (★) column is also hidden for guests.
                        // View column is always present (first column) for all users —
                        // guests are prompted to sign in when they click it.
                        $isVerifiedUser = auth()->check() && auth()->user()->hasVerifiedEmail();

                        // Column order: Class, Lesson, Description, Version, Updated, [Contributor if verified], Rating
                        $cols = [
                            'class_name'       => ['label' => 'Class',       'align' => 'left'],
                            'lesson_day'       => ['label' => 'Lesson',      'align' => 'center'],
                            'description'      => ['label' => 'Description', 'align' => 'left', 'sortable' => false],
                            'semantic_version' => ['label' => 'Version',     'align' => 'center'],
                            'updated_at'       => ['label' => 'Updated',     'align' => 'left'],
                        ];
                        if ($isVerifiedUser) {
                            $cols['author_name'] = ['label' => 'Contributor', 'align' => 'left'];
                        }
                        $cols['vote_score'] = ['label' => 'Rating', 'align' => 'center'];
                    @endphp
                    <tr>
                        {{-- "View" is always the first column for all users --}}
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">View</th>

                        @foreach ($cols as $field => $col)
                            @php
                                $isActive  = ($sortField === $field);
                                $nextOrder = ($isActive && $sortOrder === 'asc') ? 'desc' : 'asc';
                                $thAlign   = $col['align'] === 'center' ? 'text-center' : 'text-left';
                                $linkAlign = $col['align'] === 'center' ? 'justify-center w-full' : '';
                            @endphp
                            @if (!($col['sortable'] ?? true))
                                {{-- Non-sortable column (e.g. Description excerpt) --}}
                                <th class="px-4 py-3 {{ $thAlign }} text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $col['label'] }}</th>
                            @else
                                <th class="px-4 py-3 {{ $thAlign }} text-xs uppercase tracking-wider">
                                    {{-- Each sort header styled as a distinct blue button pill --}}
                                    <a href="{{ route('dashboard', array_merge(request()->query(), ['sort' => $field, 'order' => $nextOrder])) }}"
                                       class="inline-flex items-center {{ $linkAlign }} px-2 py-1 rounded font-semibold transition-colors
                                              {{ $isActive
                                                  ? 'bg-blue-600 text-white'
                                                  : 'text-blue-600 hover:bg-blue-50' }}">
                                        {{ $col['label'] }}
                                        @if ($isActive)
                                            <span class="ml-1">{!! $sortOrder === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                        @endif
                                    </a>
                                </th>
                            @endif
                        @endforeach

                        {{-- Favorites column hidden for guests --}}
                        @if($isVerifiedUser)
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider" title="Favorite">★</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($plans as $plan)
                        {{-- Clicking any data cell navigates to the plan detail page --}}
                        <tr class="hover:bg-gray-50 cursor-pointer"
                            onclick="window.location.href='{{ route('lesson-plans.show', $plan) }}'">

                            {{-- "View" button — first column, always visible --}}
                            <td class="px-4 py-3 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                                @if($isVerifiedUser)
                                    {{-- Verified users: direct link to the plan detail page --}}
                                    <a href="{{ route('lesson-plans.show', $plan) }}"
                                       class="inline-block px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                                        View
                                    </a>
                                @else
                                    {{-- Guests: open Sign In dialog instead of navigating --}}
                                    <button type="button"
                                            x-data
                                            @click="$dispatch('open-auth-modal', { mode: 'login' })"
                                            class="inline-block px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md cursor-pointer">
                                        View
                                    </button>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-700">{{ $plan->class_name }}</td>
                            <td class="px-4 py-3 text-gray-700 text-center">{{ $plan->lesson_day }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs truncate max-w-[140px]">
                                @php
                                    $excerpt = $plan->description
                                        ? mb_substr($plan->description, 0, 24)
                                        : mb_substr($plan->file_name ?? '', 0, 24);
                                @endphp
                                {{ $excerpt ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 text-center font-mono text-xs">{{ $plan->semantic_version }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $plan->updated_at->format('M j, Y') }}</td>
                            @if($isVerifiedUser)
                                <td class="px-4 py-3 text-gray-700 text-xs">{{ $plan->author_name ?: 'No Teacher Name' }}</td>
                            @endif

                            {{-- Rating: display-only score (+/-) — voting happens exclusively on the detail page --}}
                            <td class="px-4 py-3 text-center">
                                @php $score = $plan->vote_score; @endphp
                                <span class="font-medium text-xs tabular-nums {{ $score > 0 ? 'text-green-600' : ($score < 0 ? 'text-red-600' : 'text-gray-400') }}">
                                    {{ $score > 0 ? '+' . $score : $score }}
                                </span>
                            </td>

                            {{-- Favorites: AJAX toggle for verified users; hidden for guests --}}
                            @if($isVerifiedUser)
                                <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                    <div x-data="{
                                        fav: {{ in_array($plan->id, $favoritedIds) ? 'true' : 'false' }},
                                        toggle() {
                                            fetch('{{ route('favorites.toggle', $plan) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                    'Accept': 'application/json'
                                                }
                                            }).then(r => {
                                                if (!r.ok) return;
                                                r.json().then(d => { this.fav = d.favorited; }).catch(() => {});
                                            }).catch(() => {});
                                        }
                                    }">
                                        <button @click="toggle" title="Toggle favorite"
                                                :class="fav ? 'text-yellow-400 hover:text-yellow-500' : 'text-gray-300 hover:text-yellow-400'"
                                                class="text-lg leading-none transition-colors">★</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            {{-- +1 for View (always present); +1 for ★ (verified only) --}}
                            <td colspan="{{ count($cols) + 1 + ($isVerifiedUser ? 1 : 0) }}" class="px-4 py-8 text-center text-gray-500">
                                No lesson plans found. {{ request('search') ? 'Try a different search.' : '' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- Pagination --}}
    @if ($plans->hasPages())
        <div class="mt-4">
            {{ $plans->links() }}
        </div>
    @endif

    {{-- Summary --}}
    <div class="mt-3 text-xs text-gray-400">
        {{ $plans->count() }} of {{ $plans->total() }} {{ Str::plural('plan', $plans->total()) }} shown
    </div>

    {{-- Upload button — only for verified users; prominent below the table --}}
    @if(auth()->check() && auth()->user()->hasVerifiedEmail())
        <div class="mt-6 flex justify-center">
            <a href="{{ route('lesson-plans.create') }}"
               class="w-full sm:w-auto sm:min-w-[260px] text-center px-6 py-3 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                Upload New Lesson
            </a>
        </div>
    @endif

</x-layout>
