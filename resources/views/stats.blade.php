<x-layout>
    <x-slot:title>Stats — ARES Education</x-slot>

    <div class="max-w-4xl mx-auto">

        {{-- Page Title --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Archive Statistics</h1>
                <p class="text-sm text-gray-500 mt-1">A snapshot of the ARES Education Lesson Plan Archive.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('stats') }}"
                   class="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1"
                   title="Refresh statistics">
                    ↻ Refresh
                </a>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors shrink-0">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>

        {{-- ── Summary Counters ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="border border-gray-200 rounded-lg p-5 text-center">
                <p class="text-4xl font-bold text-gray-900">{{ $totalPlanCount }}</p>
                <p class="text-sm text-gray-500 mt-1">Total Lesson {{ Str::plural('Plan', $totalPlanCount) }}</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-5 text-center">
                <p class="text-4xl font-bold text-gray-900">{{ $uniqueClassCount }}</p>
                <p class="text-sm text-gray-500 mt-1">Unique {{ Str::plural('Class', $uniqueClassCount) }}</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-5 text-center">
                <p class="text-4xl font-bold text-gray-900">{{ $contributorCount }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ Str::plural('Contributor', $contributorCount) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- ── Plans per Class ── --}}
            <div class="border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Plans per Class</h2>
                @forelse ($plansPerClass as $row)
                    <div class="mb-3 last:mb-0">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700 font-medium">{{ $row->class_name }}</span>
                            <span class="text-gray-500">{{ $row->plan_count }} {{ Str::plural('plan', $row->plan_count) }}</span>
                        </div>
                        {{-- Visual bar — width proportional to max --}}
                        @php $maxCount = $plansPerClass->max('plan_count'); @endphp
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-gray-700 h-2.5 rounded-full"
                                 style="width: {{ $maxCount > 0 ? round(($row->plan_count / $maxCount) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">No plans uploaded yet.</p>
                @endforelse
            </div>

            {{-- ── Top Rated Plans ── --}}
            <div class="border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Top Rated Plans</h2>
                @forelse ($topRated as $plan)
                    <div class="flex items-start justify-between mb-3 last:mb-0">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('lesson-plans.show', $plan) }}"
                               class="text-sm font-medium text-gray-900 hover:text-gray-600 underline underline-offset-2 truncate block">
                                {{ $plan->class_name }} — Day {{ $plan->lesson_day }}
                            </a>
                            <p class="text-xs text-gray-500">by {{ $plan->author->name ?? 'Unknown' }}</p>
                        </div>
                        <span class="text-sm font-bold text-green-600 ml-3 flex-shrink-0">
                            +{{ $plan->vote_score }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">No plans have been upvoted yet.</p>
                @endforelse
            </div>

            {{-- ── Top Contributors ── --}}
            <div class="border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Top Contributors</h2>
                @forelse ($topContributors as $index => $row)
                    <div class="flex items-center justify-between mb-3 last:mb-0">
                        <div class="flex items-center space-x-3">
                            <span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-sm text-gray-900 font-medium">{{ $row->author->name ?? 'Unknown' }}</span>
                        </div>
                        <span class="text-sm text-gray-500">{{ $row->upload_count }} {{ Str::plural('plan', $row->upload_count) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">No contributions yet.</p>
                @endforelse
            </div>

            {{-- ── Most Revised Plan ── --}}
            <div class="border border-gray-200 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Most Revised Plan</h2>
                @if ($mostRevisedPlan)
                    <a href="{{ route('lesson-plans.show', $mostRevisedPlan) }}"
                       class="text-sm font-medium text-gray-900 hover:text-gray-600 underline underline-offset-2">
                        {{ $mostRevisedPlan->class_name }} — Day {{ $mostRevisedPlan->lesson_day }}
                    </a>
                    <p class="text-xs text-gray-500 mt-1">
                        Originally by {{ $mostRevisedPlan->author->name ?? 'Unknown' }}
                        &middot;
                        {{ $mostRevisedPlan->family_version_count }} versions
                    </p>
                @else
                    <p class="text-sm text-gray-400 italic">No plans have been revised yet.</p>
                @endif
            </div>

        </div>

        {{-- Back link --}}
        <div class="mt-8">
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; Back to Browse</a>
        </div>

    </div>

</x-layout>
