<x-layout>
    <x-slot:title>My Contributions — ARES Education</x-slot>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Contributions</h1>

    {{-- Search & Filter Bar --}}
    <form method="GET" action="{{ route('my-contributions') }}" class="mb-3 border border-gray-200 rounded-lg p-4 sm:p-5">
        <div class="flex flex-wrap gap-3 items-end">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       placeholder="Class, Description, or any criterion..."
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
            </div>

            {{-- Class Name Filter --}}
            <div class="w-48">
                <label for="class_name" class="block text-xs font-medium text-gray-500 mb-1">Class</label>
                <select name="class_name" id="class_name"
                        onchange="this.form.requestSubmit()"
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
            <a href="{{ route('my-contributions') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900">Clear</a>
        </div>
    </form>

    {{-- Instructional text --}}
    <p class="text-sm text-gray-500 text-center mb-4">
        Sort by clicking column header; view lesson plan by clicking the row
    </p>

    {{-- Results Table --}}
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                    @php
                        // Columns: Class, Grade, Day, Official, Description, Rev, Date, Rated, My Fave, Delete
                        $cols = [
                            'class_name'       => ['label' => 'Class',       'align' => 'left'],
                            'grade'            => ['label' => 'Grade',       'align' => 'center'],
                            'lesson_day'       => ['label' => 'Day',         'align' => 'center'],
                            'is_official'      => ['label' => 'Official',    'align' => 'center'],
                            'description'      => ['label' => 'Description', 'align' => 'left'],
                            'semantic_version' => ['label' => 'Rev.',        'align' => 'center'],
                            'updated_at'       => ['label' => 'Date',        'align' => 'left'],
                            'vote_score'       => ['label' => 'Rated',       'align' => 'center'],
                            'is_favorited'     => ['label' => 'My Fave',     'align' => 'center'],
                        ];
                    @endphp
                    <tr>
                        @foreach ($cols as $field => $col)
                            @php
                                $isActive  = ($sortField === $field);
                                $nextOrder = ($isActive && $sortOrder === 'asc') ? 'desc' : 'asc';
                                $thAlign   = $col['align'] === 'center' ? 'text-center' : 'text-left';
                                $linkAlign = $col['align'] === 'center' ? 'justify-center w-full' : '';
                            @endphp
                            <th class="px-4 py-3 {{ $thAlign }} text-xs uppercase tracking-wider">
                                <a href="{{ route('my-contributions', array_merge(request()->query(), ['sort' => $field, 'order' => $nextOrder])) }}"
                                   class="inline-flex items-center {{ $linkAlign }} px-2 py-1 rounded font-semibold transition-colors
                                          {{ $isActive ? 'bg-blue-600 text-white' : 'text-blue-600 hover:bg-blue-50' }}">
                                    {{ $col['label'] }}
                                    @if ($isActive)
                                        <span class="ml-1">{!! $sortOrder === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                    @endif
                                </a>
                            </th>
                        @endforeach
                        {{-- Delete column — non-sortable --}}
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Delete</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($plans as $plan)
                        <tr class="hover:bg-gray-50 cursor-pointer"
                            onclick="window.location.href='{{ route('lesson-plans.show', $plan) }}'">

                            <td class="px-4 py-3 text-gray-700">{{ $plan->class_name }}</td>
                            <td class="px-4 py-3 text-gray-700 text-center">{{ $plan->grade }}</td>
                            <td class="px-4 py-3 text-gray-700 text-center">{{ $plan->lesson_day }}</td>
                            <td class="px-4 py-3 text-center text-xl font-bold text-gray-900">
                                {{ $plan->is_official ? '✓' : '' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs truncate max-w-[140px]">
                                <x-lesson-description-excerpt :plan="$plan" />
                            </td>
                            <td class="px-4 py-3 text-gray-700 text-center font-mono text-xs">{{ $plan->semantic_version }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $plan->updated_at->format('d/m/y') }}</td>

                            {{-- Rating --}}
                            <td class="px-4 py-3 text-center">
                                @php $score = $plan->vote_score; @endphp
                                <span class="font-medium text-xs tabular-nums {{ $score > 0 ? 'text-green-600' : ($score < 0 ? 'text-red-600' : 'text-gray-400') }}">
                                    {{ $score > 0 ? '+' . $score : $score }}
                                </span>
                            </td>

                            {{-- Favorites toggle --}}
                            <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                <x-favorite-toggle :plan="$plan" :is-favorited="in_array($plan->id, $favoritedIds)" />
                            </td>

                            {{-- Delete: only enabled for non-official plans --}}
                            <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                @if (!$plan->is_official)
                                    <form method="POST" action="{{ route('lesson-plans.destroy', $plan) }}"
                                          onsubmit="return confirm('Delete this lesson plan? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                            Del
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($cols) + 1 }}" class="px-4 py-8 text-center text-gray-500">
                                You haven't uploaded any lesson plans yet.
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


</x-layout>
