<x-layout>
    <x-slot:title>Compare Versions — {{ $lessonPlan->class_name }} Lesson {{ $lessonPlan->lesson_day }}</x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Compare Versions</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $lessonPlan->class_name }} Lesson {{ $lessonPlan->lesson_day }}
                </p>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                    Print / Save PDF
                </button>
                <a href="{{ route('lesson-plans.show', $lessonPlan) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                    &larr; Back to Details
                </a>
            </div>
        </div>

        {{-- Version selector form --}}
        <div class="border border-gray-200 rounded-lg p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.lesson-plans.compare', $lessonPlan) }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[220px]">
                    <label for="compare_to" class="block text-xs font-medium text-gray-500 mb-1">Compare Current Version To</label>
                    <select id="compare_to" name="compare_to"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                        <option value="">Auto: previous version</option>
                        @foreach ($versions as $version)
                            @if ($version->id !== $lessonPlan->id)
                                <option value="{{ $version->id }}" {{ $targetPlan && $targetPlan->id === $version->id ? 'selected' : '' }}>
                                    v{{ $version->semantic_version }} — {{ $version->created_at->format('M j, Y') }} — {{ $version->author->name ?? 'Anonymous' }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                    Compare
                </button>
            </form>
        </div>

        {{-- Current vs baseline version cards --}}
        <div class="border border-gray-200 rounded-lg p-4 sm:p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Current</p>
                    <p class="font-medium text-gray-900 mt-1">v{{ $lessonPlan->semantic_version }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $lessonPlan->author->name ?? 'Anonymous' }} · {{ $lessonPlan->created_at->format('M j, Y g:ia') }} UTC
                    </p>
                </div>
                <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Baseline</p>
                    @if ($targetPlan)
                        <p class="font-medium text-gray-900 mt-1">v{{ $targetPlan->semantic_version }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $targetPlan->author->name ?? 'Anonymous' }} · {{ $targetPlan->created_at->format('M j, Y g:ia') }} UTC
                        </p>
                    @else
                        <p class="font-medium text-gray-500 mt-1">No baseline selected</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Warning banner (unsupported type, missing file, too large, no predecessor) --}}
        @if ($warning)
            <div class="border border-amber-200 bg-amber-50 rounded-lg p-4 text-sm text-amber-800">
                {{ $warning }}
            </div>
        @endif

        {{-- Diff summary stats --}}
        @if ($diffSummary)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="border border-green-200 bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-700">+{{ $diffSummary['added'] }}</p>
                    <p class="text-xs text-green-700 uppercase tracking-wider mt-1">Lines Added</p>
                </div>
                <div class="border border-red-200 bg-red-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-red-700">-{{ $diffSummary['removed'] }}</p>
                    <p class="text-xs text-red-700 uppercase tracking-wider mt-1">Lines Removed</p>
                </div>
                <div class="border border-blue-200 bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-700">{{ $diffSummary['changed'] }}</p>
                    <p class="text-xs text-blue-700 uppercase tracking-wider mt-1">Lines Changed (Est.)</p>
                </div>
            </div>
        @endif

        {{-- Line-level diff output --}}
        @if (!empty($diffOps))
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-900">Line-Level Diff</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Equal lines are shown in neutral color; additions/removals are highlighted.</p>
                </div>
                <div class="overflow-x-auto">
                    <div class="font-mono text-xs whitespace-pre">
                        @foreach ($diffOps as $op)
                            @php
                                $prefix = $op['type'] === 'add' ? '+' : ($op['type'] === 'remove' ? '-' : ' ');
                                $lineClass = $op['type'] === 'add'
                                    ? 'bg-green-50 text-green-900'
                                    : ($op['type'] === 'remove'
                                        ? 'bg-red-50 text-red-900'
                                        : 'text-gray-600');
                            @endphp
                            <div class="px-4 py-1 {{ $lineClass }}">{{ $prefix }} {{ $op['line'] }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layout>
