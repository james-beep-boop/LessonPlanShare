<x-layout>
    <x-slot:title>Admin Panel — ARES Education</x-slot>

    <div class="max-w-6xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Admin Panel</h1>
                <p class="text-sm text-gray-500 mt-1">Manage lesson plans and users. Deletions are permanent.</p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors shrink-0">
                &larr; Back to Dashboard
            </a>
        </div>

        {{-- ── Counters ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="border border-gray-200 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-gray-900">{{ $uniqueClassCount }}</p>
                <p class="text-xs text-gray-500 mt-1">Unique {{ Str::plural('Class', $uniqueClassCount) }}</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-gray-900">{{ $totalPlanCount }}</p>
                <p class="text-xs text-gray-500 mt-1">Lesson {{ Str::plural('Plan', $totalPlanCount) }}</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-gray-900">{{ $contributorCount }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ Str::plural('Contributor', $contributorCount) }}</p>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             ANALYTICS CHARTS
        ══════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            {{-- Graph 1: Engagement --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Engagement (cumulative)</h3>
                <canvas id="engagementChart"></canvas>
            </div>

            {{-- Graph 2: Content --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Content (cumulative)</h3>
                <canvas id="contentChart"></canvas>
            </div>

        </div>

        {{-- Chart.js — admin page only --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
        <script>
        (function () {
            const labels   = @json($chartLabels);
            const axisOpts = {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { ticks: { maxTicksLimit: 16, maxRotation: 45 } }
            };
            const lineOpts = { tension: 0.3, fill: true, pointRadius: 2, borderWidth: 2 };

            new Chart(document.getElementById('engagementChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { ...lineOpts, label: 'Unique Users', data: @json($userCumulative),
                          borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.08)' },
                        { ...lineOpts, label: 'Total Logins',  data: @json($loginCumulative),
                          borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.08)' },
                    ]
                },
                options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: axisOpts }
            });

            new Chart(document.getElementById('contentChart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { ...lineOpts, label: 'Official Plans', data: @json($officialCumulative),
                          borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.08)' },
                        { ...lineOpts, label: 'All Documents',  data: @json($allPlansCumulative),
                          borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.08)' },
                        { ...lineOpts, label: 'Downloads',      data: @json($downloadCumulative),
                          borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.08)' },
                    ]
                },
                options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: axisOpts }
            });
        }());
        </script>

        {{-- ══════════════════════════════════════════════════════════
             LESSON PLANS TABLE
        ══════════════════════════════════════════════════════════ --}}
        <div class="mb-12">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Lesson Plans ({{ $plans->total() }})</h2>

            {{-- Search form for plans --}}
            <form method="GET" action="{{ route('admin.index') }}" class="mb-3 flex gap-2 flex-wrap">
                {{-- Preserve user table state --}}
                <input type="hidden" name="user_search" value="{{ $userSearch }}">
                <input type="hidden" name="user_sort"   value="{{ $userSort }}">
                <input type="hidden" name="user_order"  value="{{ $userOrder }}">
                <input type="text" name="plan_search" value="{{ $planSearch }}"
                       placeholder="Class, Description, Contributor, or any criterion..."
                       class="flex-1 min-w-[200px] border border-gray-300 rounded-md px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                <button type="submit"
                        class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                    Search
                </button>
                @if ($planSearch)
                    <a href="{{ route('admin.index', ['user_search' => $userSearch, 'user_sort' => $userSort, 'user_order' => $userOrder]) }}"
                       class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900">Clear</a>
                @endif
            </form>

            {{-- Bulk-delete form (checkboxes in the table reference this via form="bulk-plans-form") --}}
            <form id="bulk-plans-form"
                  method="POST"
                  action="{{ route('admin.lesson-plans.bulk-delete') }}"
                  onsubmit="return confirm('Delete selected lesson plans? This cannot be undone.')">
                @csrf
            </form>

            <div class="mb-2">
                <button type="submit" form="bulk-plans-form"
                        class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 transition-colors">
                    Delete Selected Plans
                </button>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox"
                                           onclick="document.querySelectorAll('.plan-cb').forEach(cb => cb.checked = this.checked)"
                                           class="rounded border-gray-300">
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Del</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Diff</th>
                                @php
                                    // Sortable plan column headers
                                    $planCols = [
                                        'is_official'      => ['label' => 'Main',        'align' => 'center'],
                                        'class_name'       => ['label' => 'Class',       'align' => 'left'],
                                        'grade'            => ['label' => 'Grade',       'align' => 'center'],
                                        'lesson_day'       => ['label' => 'Lesson',      'align' => 'center'],
                                        'description'      => ['label' => 'Description', 'align' => 'left'],
                                        'author_name'      => ['label' => 'Contributor', 'align' => 'left'],
                                        'semantic_version' => ['label' => 'Ver.',        'align' => 'center'],
                                        'updated_at'       => ['label' => 'Update',      'align' => 'left'],
                                    ];
                                @endphp
                                @foreach ($planCols as $field => $col)
                                    @php
                                        $isActive  = ($planSort === $field);
                                        $nextOrder = ($isActive && $planOrder === 'asc') ? 'desc' : 'asc';
                                        $thAlign   = $col['align'] === 'center' ? 'text-center' : 'text-left';
                                        $linkAlign = $col['align'] === 'center' ? 'justify-center w-full' : '';
                                    @endphp
                                    @if (!($col['sortable'] ?? true))
                                        <th class="px-3 py-3 {{ $thAlign }} text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $col['label'] }}</th>
                                    @else
                                        <th class="px-3 py-3 {{ $thAlign }} text-xs uppercase tracking-wider">
                                            <a href="{{ route('admin.index', array_merge(request()->query(), ['plan_sort' => $field, 'plan_order' => $nextOrder, 'plans_page' => 1])) }}"
                                               class="inline-flex items-center {{ $linkAlign }} px-2 py-0.5 rounded font-semibold transition-colors
                                                      {{ $isActive ? 'bg-blue-600 text-white' : 'text-blue-600 hover:bg-blue-50' }}">
                                                {{ $col['label'] }}
                                                @if ($isActive)
                                                    <span class="ml-1">{!! $planOrder === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                                @endif
                                            </a>
                                        </th>
                                    @endif
                                @endforeach
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">File</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($plans as $plan)
                                <tr class="hover:bg-red-50">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox"
                                               name="ids[]"
                                               value="{{ $plan->id }}"
                                               form="bulk-plans-form"
                                               class="plan-cb rounded border-gray-300">
                                    </td>
                                    <td class="px-3 py-2">
                                        <form method="POST"
                                              action="{{ route('admin.lesson-plans.destroy', $plan) }}"
                                              onsubmit="return confirm('Delete this lesson plan? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                                Del
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('admin.lesson-plans.compare', $plan) }}"
                                           class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded hover:bg-blue-200 transition-colors">
                                            Diff
                                        </a>
                                    </td>
                                    {{-- Official: ✓ for official plans; "Set Official" button for others --}}
                                    <td class="px-3 py-2 text-center">
                                        @if ($plan->is_official)
                                            <span class="text-xl font-bold text-gray-900">✓</span>
                                        @else
                                            <form method="POST"
                                                  action="{{ route('admin.lesson-plans.set-official', $plan) }}"
                                                  onsubmit="return confirm('Make this the Official version for {{ addslashes($plan->class_name) }} Lesson {{ $plan->lesson_day }}?')">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded hover:bg-blue-200 transition-colors">
                                                    Set Official
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ $plan->class_name }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-center">{{ $plan->grade }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-center">{{ $plan->lesson_day }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs truncate max-w-[120px]">
                                        <x-lesson-description-excerpt :plan="$plan" />
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 text-xs">{{ $plan->author_name ?? 'Anonymous' }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-center font-mono text-xs">{{ $plan->semantic_version }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs">{{ $plan->updated_at->format('M j, Y') }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs font-mono truncate max-w-[160px]">{{ $plan->file_name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-4 py-6 text-center text-gray-400">
                                        No lesson plans{{ $planSearch ? ' matching "' . e($planSearch) . '"' : '' }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Plans pagination --}}
            @if ($plans->hasPages())
                <div class="mt-3">
                    {{ $plans->links() }}
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════════════
             REGISTERED USERS TABLE
        ══════════════════════════════════════════════════════════ --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Registered Users ({{ $users->total() }})</h2>

            {{-- Search form for users --}}
            <form method="GET" action="{{ route('admin.index') }}" class="mb-3 flex gap-2 flex-wrap">
                {{-- Preserve plan table state --}}
                <input type="hidden" name="plan_search" value="{{ $planSearch }}">
                <input type="hidden" name="plan_sort"   value="{{ $planSort }}">
                <input type="hidden" name="plan_order"  value="{{ $planOrder }}">
                <input type="text" name="user_search" value="{{ $userSearch }}"
                       placeholder="Search name or email…"
                       class="flex-1 min-w-[200px] border border-gray-300 rounded-md px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                <button type="submit"
                        class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                    Search
                </button>
                @if ($userSearch)
                    <a href="{{ route('admin.index', ['plan_search' => $planSearch, 'plan_sort' => $planSort, 'plan_order' => $planOrder]) }}"
                       class="px-4 py-2 text-sm text-gray-500 hover:text-gray-900">Clear</a>
                @endif
            </form>

            {{-- Bulk-delete form for users --}}
            <form id="bulk-users-form"
                  method="POST"
                  action="{{ route('admin.users.bulk-delete') }}"
                  onsubmit="return confirm('Delete selected users? This cannot be undone.')">
                @csrf
            </form>

            <div class="mb-2">
                <button type="submit" form="bulk-users-form"
                        class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 transition-colors">
                    Delete Selected Users
                </button>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox"
                                           onclick="document.querySelectorAll('.user-cb').forEach(cb => cb.checked = this.checked)"
                                           class="rounded border-gray-300">
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Del</th>
                                @php
                                    $userCols = [
                                        'name'               => ['label' => 'Contributor',  'align' => 'left'],
                                        'email'              => ['label' => 'Email',         'align' => 'left'],
                                        'email_verified_at'  => ['label' => 'Verified',      'align' => 'center'],
                                        'created_at'         => ['label' => 'Registered',    'align' => 'left'],
                                    ];
                                @endphp
                                @foreach ($userCols as $field => $col)
                                    @php
                                        $isActive  = ($userSort === $field);
                                        $nextOrder = ($isActive && $userOrder === 'asc') ? 'desc' : 'asc';
                                        $thAlign   = $col['align'] === 'center' ? 'text-center' : 'text-left';
                                        $linkAlign = $col['align'] === 'center' ? 'justify-center w-full' : '';
                                    @endphp
                                    <th class="px-3 py-3 {{ $thAlign }} text-xs uppercase tracking-wider">
                                        <a href="{{ route('admin.index', array_merge(request()->query(), ['user_sort' => $field, 'user_order' => $nextOrder, 'users_page' => 1])) }}"
                                           class="inline-flex items-center {{ $linkAlign }} px-2 py-0.5 rounded font-semibold transition-colors
                                                  {{ $isActive ? 'bg-blue-600 text-white' : 'text-blue-600 hover:bg-blue-50' }}">
                                            {{ $col['label'] }}
                                            @if ($isActive)
                                                <span class="ml-1">{!! $userOrder === 'asc' ? '&#9650;' : '&#9660;' !!}</span>
                                            @endif
                                        </a>
                                    </th>
                                @endforeach
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($users as $u)
                                <tr class="hover:bg-red-50 {{ $u->id === auth()->id() ? 'bg-blue-50' : '' }}">
                                    <td class="px-3 py-2 text-center">
                                        @if ($u->id !== auth()->id())
                                            <input type="checkbox"
                                                   name="user_ids[]"
                                                   value="{{ $u->id }}"
                                                   form="bulk-users-form"
                                                   class="user-cb rounded border-gray-300">
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($u->id !== auth()->id())
                                            <form method="POST"
                                                  action="{{ route('admin.users.destroy', $u) }}"
                                                  onsubmit="return confirm('Delete user {{ addslashes($u->email) }}? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                                    Del
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400 italic">you</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ $u->name }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-xs">{{ $u->email }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($u->email_verified_at)
                                            <span class="text-green-600 text-xs font-medium">Yes</span>
                                        @else
                                            <span class="text-red-500 text-xs font-medium">No</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-500 text-xs">{{ $u->created_at->format('M j, Y') }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($u->is_admin)
                                            <span class="text-blue-600 text-xs font-medium">Yes</span>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-1.5 flex-wrap">

                                        {{-- Verify button (unverified users only) --}}
                                        @if (! $u->email_verified_at)
                                            <div x-data="{ sent: false }" class="inline">
                                                <button type="button"
                                                        @click="
                                                            fetch('{{ route('users.send-verification', $u) }}', {
                                                                method: 'POST',
                                                                headers: {
                                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                                    'Accept': 'application/json'
                                                                }
                                                            }).then(r => {
                                                                if (r.ok) {
                                                                    sent = true;
                                                                    setTimeout(() => sent = false, 5000);
                                                                }
                                                            });
                                                        "
                                                        :class="sent ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                                        class="px-2 py-1 text-xs font-medium rounded-md transition-colors"
                                                        x-text="sent ? 'Email Sent' : 'Verify'">
                                                </button>
                                            </div>
                                        @endif

                                        {{-- Grant admin: any admin can promote a non-admin (not self) --}}
                                        @if ($u->id !== auth()->id() && ! $u->is_admin)
                                            <form method="POST"
                                                  action="{{ route('admin.users.toggle-admin', $u) }}"
                                                  onsubmit="return confirm('Grant admin privileges to {{ addslashes($u->name) }}?')">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded hover:bg-blue-200 transition-colors">
                                                    Make Admin
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Revoke admin: only super-admin can demote (not self) --}}
                                        @if ($u->id !== auth()->id() && $u->is_admin && auth()->user()->email === 'priority2@protonmail.ch')
                                            <form method="POST"
                                                  action="{{ route('admin.users.toggle-admin', $u) }}"
                                                  onsubmit="return confirm('Revoke admin privileges from {{ addslashes($u->name) }}?')">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded hover:bg-orange-200 transition-colors">
                                                    Revoke Admin
                                                </button>
                                            </form>
                                        @endif

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-400">
                                        No users{{ $userSearch ? ' matching "' . e($userSearch) . '"' : '' }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Users pagination --}}
            @if ($users->hasPages())
                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════════════
             OFFICIAL LESSON PLANS TABLE
        ══════════════════════════════════════════════════════════ --}}
        <div class="mt-12">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Official Lesson Plans ({{ $officialPlans->count() }})</h2>
            <p class="text-xs text-gray-500 mb-3">One Official plan per Class + Lesson number. Use "Set Official" in the table above to change the designation.</p>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Official</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Lesson</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contributor</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ver.</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Updated</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">File</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($officialPlans as $plan)
                                <tr class="hover:bg-blue-50">
                                    <td class="px-3 py-2 text-center text-xl font-bold text-gray-900">✓</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $plan->class_name }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-center">{{ $plan->grade }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-center">{{ $plan->lesson_day }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs truncate max-w-[120px]">
                                        <x-lesson-description-excerpt :plan="$plan" />
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 text-xs">{{ $plan->author_name ?? 'Anonymous' }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-center font-mono text-xs">{{ $plan->semantic_version }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs">{{ $plan->updated_at->format('M j, Y') }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs font-mono truncate max-w-[160px]">{{ $plan->file_name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-400">
                                        No Official lesson plans have been designated yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</x-layout>
