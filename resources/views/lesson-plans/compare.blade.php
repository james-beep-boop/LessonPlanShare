<x-layout>
    <x-slot:title>Compare Versions — {{ $lessonPlan->class_name }} Grade {{ $lessonPlan->grade }} Lesson {{ $lessonPlan->lesson_day }} — ARES Education</x-slot>

    <div class="max-w-5xl mx-auto space-y-6"
         x-data="{ sideBySide: false }">

        {{-- Page header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Compare Versions</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $lessonPlan->class_name }}, Grade {{ $lessonPlan->grade }}, Lesson {{ $lessonPlan->lesson_day }}
                </p>
            </div>
            <div class="flex gap-2 flex-wrap shrink-0">
                <a href="{{ route('lesson-plans.show', $lessonPlan) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors">
                    &larr; Back to Lesson Details
                </a>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>

        {{-- Current version info card --}}
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 text-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Comparing from (current)</p>
            <p class="font-medium text-gray-900">
                v{{ $lessonPlan->semantic_version }}
                — {{ $lessonPlan->author->name ?? 'Anonymous' }}
                — {{ $lessonPlan->created_at->format('M j, Y') }}
            </p>
        </div>

        {{-- Revision selector table --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-900">Select a Revision to Compare Against</p>
                <p class="text-xs text-gray-500 mt-0.5">Click a row to load the diff below</p>
            </div>
            @if ($otherVersions->isEmpty())
                <p class="px-4 py-4 text-sm text-gray-500 italic">No other revisions exist for this lesson plan yet.</p>
            @else
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
                        @foreach ($otherVersions as $v)
                            <tr class="border-t border-gray-100 cursor-pointer
                                       {{ $targetPlan && $targetPlan->id === $v->id ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                                onclick="window.location='{{ route('lesson-plans.compare', [$lessonPlan, 'compare_to' => $v->id]) }}'">
                                <td class="px-4 py-2 font-medium">
                                    v{{ $v->semantic_version }}
                                    @if ($targetPlan && $targetPlan->id === $v->id)
                                        <span class="ml-1.5 text-xs font-normal text-blue-600">(selected)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $v->is_official ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-2">{{ $v->author->name ?? 'Anonymous' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $v->created_at->format('M j, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Warning banner --}}
        @if ($warning)
            <div class="border border-amber-200 bg-amber-50 rounded-lg p-4 text-sm text-amber-800">
                {{ $warning }}
            </div>
        @endif

        {{-- Diff output (only when a comparison is ready) --}}
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
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">Inline Diff</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Baseline: v{{ $targetPlan->semantic_version }} ({{ $targetPlan->author->name ?? 'Anonymous' }})
                        &rarr; Current: v{{ $lessonPlan->semantic_version }}
                    </p>
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
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <p class="text-sm font-semibold text-gray-900">Side-by-Side Diff</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Left: v{{ $targetPlan->semantic_version }} (baseline) &nbsp;|&nbsp;
                        Right: v{{ $lessonPlan->semantic_version }} (current)
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full font-mono text-xs border-collapse">
                        <thead class="bg-gray-100 text-gray-500 text-left">
                            <tr>
                                <th class="px-3 py-1.5 w-1/2 border-r border-gray-200 font-medium">
                                    v{{ $targetPlan->semantic_version }} — Baseline
                                </th>
                                <th class="px-3 py-1.5 w-1/2 font-medium">
                                    v{{ $lessonPlan->semantic_version }} — Current
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sideBySide as $row)
                                @php
                                    $leftClass = match($row['type']) {
                                        'remove' => 'bg-red-50 text-red-900',
                                        'change' => 'bg-red-50 text-red-900',
                                        default  => 'text-gray-600',
                                    };
                                    $rightClass = match($row['type']) {
                                        'add'    => 'bg-green-50 text-green-900',
                                        'change' => 'bg-green-50 text-green-900',
                                        default  => 'text-gray-600',
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

        @elseif ($targetPlan && !$warning)
            <div class="border border-gray-200 bg-gray-50 rounded-lg p-4 text-sm text-gray-500 italic">
                No differences found between the selected versions.
            </div>
        @endif

    </div>
</x-layout>
