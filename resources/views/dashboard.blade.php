<x-layout>
    <x-slot:title>Dashboard - Lesson Plan Exchange</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Browse Lesson Plans</h1>
        <p class="text-sm text-gray-600 mt-1">Search, sort, and discover lesson plans shared by teachers.</p>
    </div>

    {{-- Search & Filter Bar --}}
    <form method="GET" action="{{ route('dashboard') }}" class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap gap-3 items-end">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       placeholder="Class name, document name, or author..."
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Class Name Filter --}}
            <div class="w-48">
                <label for="class_name" class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                <select name="class_name" id="class_name"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Classes</option>
                    @foreach ($classNames as $cn)
                        <option value="{{ $cn }}" {{ request('class_name') === $cn ? 'selected' : '' }}>{{ $cn }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Show All Versions Toggle --}}
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="show_all_versions" id="show_all_versions" value="1"
                       {{ request('show_all_versions') ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="show_all_versions" class="text-sm text-gray-600">Show all versions</label>
            </div>

            {{-- Buttons --}}
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                Search
            </button>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Clear</a>
        </div>
    </form>

    {{-- Results Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        @php
                            $cols = [
                                'name'           => 'Document Name',
                                'class_name'     => 'Class',
                                'lesson_day'     => 'Day #',
                                'version_number' => 'Version',
                                'author'         => 'Author',
                                'vote_score'     => 'Rating',
                                'updated_at'     => 'Updated',
                            ];
                        @endphp
                        @foreach ($cols as $field => $label)
                            @php
                                $isActive = ($sortField === $field);
                                $nextOrder = ($isActive && $sortOrder === 'asc') ? 'desc' : 'asc';
                            @endphp
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <a href="{{ route('dashboard', array_merge(request()->query(), ['sort' => $field, 'order' => $nextOrder])) }}"
                                   class="inline-flex items-center hover:text-indigo-600 {{ $isActive ? 'text-indigo-600' : '' }}">
                                    {{ $label }}
                                    @if ($isActive)
                                        <span class="ml-1">{{ $sortOrder === 'asc' ? '&#9650;' : '&#9660;' }}</span>
                                    @endif
                                </a>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">File</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($plans as $plan)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('lesson-plans.show', $plan) }}"
                                   class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    {{ $plan->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $plan->class_name }}</td>
                            <td class="px-4 py-3 text-gray-700 text-center">{{ $plan->lesson_day }}</td>
                            <td class="px-4 py-3 text-gray-700 text-center">v{{ $plan->version_number }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $plan->author->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-vote-buttons :score="$plan->vote_score" :readonly="true" />
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $plan->updated_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($plan->file_path)
                                    <a href="{{ route('lesson-plans.download', $plan) }}"
                                       class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                        Download
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
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
    <div class="mt-3 text-xs text-gray-500">
        Showing {{ $plans->firstItem() ?? 0 }}–{{ $plans->lastItem() ?? 0 }} of {{ $plans->total() }} plans
    </div>

</x-layout>
