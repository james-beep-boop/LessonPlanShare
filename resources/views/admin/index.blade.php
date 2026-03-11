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

            {{-- Plans table flash messages --}}
            @if (session('plans_success'))
                <div class="mb-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('plans_success') }}
                </div>
            @endif
            @if (session('plans_error'))
                <div class="mb-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                    {{ session('plans_error') }}
                </div>
            @endif

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
                                @php
                                    // Sortable plan column headers — "Diff" column removed; "Main" renamed to "Official"
                                    $planCols = [
                                        'is_official'      => ['label' => 'Official',     'align' => 'center'],
                                        'class_name'       => ['label' => 'Class',        'align' => 'left'],
                                        'grade'            => ['label' => 'Grade',        'align' => 'center'],
                                        'lesson_day'       => ['label' => 'Lesson',       'align' => 'center'],
                                        'description'      => ['label' => 'Description',  'align' => 'left'],
                                        'author_name'      => ['label' => 'Contributor',  'align' => 'left'],
                                        'semantic_version' => ['label' => 'Ver.',         'align' => 'center'],
                                        'updated_at'       => ['label' => 'Update',       'align' => 'left'],
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
                                    <td colspan="11" class="px-4 py-6 text-center text-gray-400">
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

            {{-- Users table flash messages --}}
            @if (session('users_success'))
                <div class="mb-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('users_success') }}
                </div>
            @endif
            @if (session('users_error'))
                <div class="mb-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                    {{ session('users_error') }}
                </div>
            @endif

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
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Make Admin</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Password</th>
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
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($users as $u)
                                <tr class="hover:bg-red-50 {{ $u->id === auth()->id() ? 'bg-blue-50' : '' }}">

                                    {{-- Checkbox --}}
                                    <td class="px-3 py-2 text-center">
                                        @if ($u->id !== auth()->id())
                                            <input type="checkbox"
                                                   name="user_ids[]"
                                                   value="{{ $u->id }}"
                                                   form="bulk-users-form"
                                                   class="user-cb rounded border-gray-300">
                                        @endif
                                    </td>

                                    {{-- Del (requires checkbox to be checked first) --}}
                                    <td class="px-3 py-2">
                                        @if ($u->id !== auth()->id())
                                            <form method="POST"
                                                  action="{{ route('admin.users.destroy', $u) }}"
                                                  onsubmit="
                                                      var cb = document.querySelector('.user-cb[value=\'{{ $u->id }}\']');
                                                      if (!cb || !cb.checked) { alert('Check the checkbox for this user first.'); return false; }
                                                      return confirm('Delete user {{ addslashes($u->email) }}? This cannot be undone.');
                                                  ">
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

                                    {{-- Admin (Yes / —) --}}
                                    <td class="px-3 py-2 text-center">
                                        @if ($u->is_admin)
                                            <span class="text-blue-600 text-xs font-medium">Yes</span>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- Make Admin / Revoke Admin column --}}
                                    <td class="px-3 py-2">
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
                                        @elseif ($u->id !== auth()->id() && $u->is_admin && auth()->user()->email === config('app.super_admin_email'))
                                            <form method="POST"
                                                  action="{{ route('admin.users.toggle-admin', $u) }}"
                                                  onsubmit="return confirm('Revoke admin privileges from {{ addslashes($u->name) }}?')">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded hover:bg-orange-200 transition-colors">
                                                    Revoke Admin
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- Password (inline admin reset) --}}
                                    <td class="px-3 py-2">
                                        @if ($u->id !== auth()->id())
                                            <div x-data="{ show: false, pwd: '', saving: false, saved: false, err: '' }" class="flex items-center gap-1 flex-wrap">
                                                <button x-show="!show" @click="show = true"
                                                        class="px-2 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded hover:bg-purple-200 transition-colors">
                                                    Reset
                                                </button>
                                                <template x-if="show">
                                                    <div class="flex items-center gap-1 flex-wrap">
                                                        <input type="password" x-model="pwd" placeholder="New password (min 8)"
                                                               class="border border-gray-300 rounded px-1.5 py-0.5 text-xs w-32 focus:outline-none focus:ring-1 focus:ring-gray-400">
                                                        <button type="button"
                                                                @click="
                                                                    if (!pwd || pwd.length < 8) { err = 'Min 8 chars'; return; }
                                                                    saving = true; err = '';
                                                                    fetch('{{ route('admin.users.reset-password', $u) }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'Content-Type': 'application/json',
                                                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                                            'Accept': 'application/json'
                                                                        },
                                                                        body: JSON.stringify({ password: pwd })
                                                                    })
                                                                    .then(r => {
                                                                        if (r.status === 419) throw new Error('Session expired — refresh the page.');
                                                                        if (!r.ok) return r.json().then(j => { throw new Error(j.message || 'Save failed.'); });
                                                                        return r.json();
                                                                    })
                                                                    .then(() => { saved = true; show = false; pwd = ''; setTimeout(() => saved = false, 5000); })
                                                                    .catch(e => { err = e.message || 'Error'; })
                                                                    .finally(() => { saving = false; });
                                                                "
                                                                :disabled="saving"
                                                                class="px-2 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors disabled:opacity-60">
                                                            Save
                                                        </button>
                                                        <button type="button" @click="show = false; pwd = ''; err = ''"
                                                                class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded hover:bg-gray-200 transition-colors">
                                                            ✕
                                                        </button>
                                                    </div>
                                                </template>
                                                <span x-show="saved" x-cloak class="text-xs text-green-600 font-medium">Saved!</span>
                                                <span x-show="err" x-cloak x-text="err" class="text-xs text-red-600"></span>
                                            </div>
                                        @else
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- Contributor, Email, Verified, Registered --}}
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

                                    {{-- Actions: Verify (if unverified) --}}
                                    <td class="px-3 py-2">
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
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-6 text-center text-gray-400">
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
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-400">
                                        No Official lesson plans have been designated yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             CHANGE LESSON PLAN FAMILY AND FILENAME
        ══════════════════════════════════════════════════════════ --}}
        <div class="mt-12" x-data="relocateTable(@js($allPlansFlat), @js($classNamesList), @js($gradesList), @js($daysList))">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Change Lesson Plan Family and Filename</h2>
            <p class="text-xs text-gray-500 mb-3">Click a row to expand the editor. Change class, grade, or lesson number and save to rename the file and update the database.</p>

            {{-- Conflict resolution modal --}}
            <div x-show="showConflict" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6" @click.away="showConflict = false">
                    <p class="text-gray-900 font-semibold text-center mb-2">Family Conflict</p>
                    <p class="text-sm text-gray-600 text-center mb-6">
                        Plans already exist for
                        <span class="font-medium" x-text="conflictTarget"></span>.
                        How should this plan be saved?
                    </p>
                    <div class="flex flex-col gap-2">
                        <button type="button" @click="resolveConflict('overwrite')"
                                class="w-full px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors">
                            Overwrite (join that family)
                        </button>
                        <button type="button" @click="resolveConflict('suffix')"
                                class="w-full px-4 py-2 text-sm font-medium text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                            Save with suffix (.1, .2 …)
                        </button>
                        <button type="button" @click="showConflict = false; pendingPayload = null"
                                class="w-full px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                            Discard Changes
                        </button>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Official</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Lesson</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contributor</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ver.</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">File</th>
                            </tr>
                        </thead>
                        {{-- Each iteration produces a valid <tbody> (multiple <tbody> in a table is valid HTML).
                             This avoids the nested-tbody bug that misaligned column headers. --}}
                        <template x-for="(plan, idx) in allPlans" :key="plan.id">
                            <tbody>
                                {{-- Data row --}}
                                <tr class="border-t border-gray-100 cursor-pointer hover:bg-amber-50"
                                    :class="expandedId === plan.id ? 'bg-amber-50' : ''"
                                    @click="toggleExpand(plan)">
                                    <td class="px-3 py-2 text-center">
                                        <span x-show="plan.is_official" class="text-xl font-bold text-gray-900">✓</span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700" x-text="plan.class_name"></td>
                                    <td class="px-3 py-2 text-gray-700 text-center" x-text="plan.grade"></td>
                                    <td class="px-3 py-2 text-gray-700 text-center" x-text="plan.lesson_day"></td>
                                    <td class="px-3 py-2 text-gray-700 text-xs" x-text="plan.author_name"></td>
                                    <td class="px-3 py-2 text-gray-700 text-center font-mono text-xs" x-text="plan.version"></td>
                                    <td class="px-3 py-2 text-gray-500 text-xs font-mono truncate max-w-[160px]" x-text="plan.file_name || '—'"></td>
                                </tr>

                                {{-- Inline editor row (shown when this row is expanded) --}}
                                <tr x-show="expandedId === plan.id" x-cloak class="border-t border-amber-200 bg-amber-50">
                                    <td colspan="7" class="px-4 py-4">
                                        <div class="space-y-3">
                                            <p class="text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                                Editing: <span class="normal-case font-normal" x-text="plan.class_name + ' G' + plan.grade + ' Lesson ' + plan.lesson_day + ' v' + plan.version"></span>
                                            </p>

                                            {{-- Change Class --}}
                                            <div class="flex items-start gap-3">
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer pt-1 whitespace-nowrap">
                                                    <input type="checkbox" x-model="edit.changeClass" class="rounded border-gray-300">
                                                    <span>Change Class?</span>
                                                </label>
                                                <div x-show="edit.changeClass" x-cloak class="flex items-center gap-2">
                                                    <select x-model="edit.classValue"
                                                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                                                        <template x-for="cn in classNames" :key="cn">
                                                            <option :value="cn" x-text="cn"></option>
                                                        </template>
                                                        <option value="__new__">Add New Value…</option>
                                                    </select>
                                                    <input x-show="edit.classValue === '__new__'" x-cloak
                                                           x-model="edit.classNew"
                                                           type="text" placeholder="New class name"
                                                           class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 w-40">
                                                </div>
                                            </div>

                                            {{-- Change Grade --}}
                                            <div class="flex items-start gap-3">
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer pt-1 whitespace-nowrap">
                                                    <input type="checkbox" x-model="edit.changeGrade" class="rounded border-gray-300">
                                                    <span>Change Grade?</span>
                                                </label>
                                                <div x-show="edit.changeGrade" x-cloak class="flex items-center gap-2">
                                                    <select x-model="edit.gradeValue"
                                                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                                                        <template x-for="g in grades" :key="g">
                                                            <option :value="String(g)" x-text="'Grade ' + g"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Change Day --}}
                                            <div class="flex items-start gap-3">
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer pt-1 whitespace-nowrap">
                                                    <input type="checkbox" x-model="edit.changeDay" class="rounded border-gray-300">
                                                    <span>Change Lesson?</span>
                                                </label>
                                                <div x-show="edit.changeDay" x-cloak class="flex items-center gap-2">
                                                    <select x-model="edit.dayValue"
                                                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                                                        <template x-for="d in days" :key="d">
                                                            <option :value="String(d)" x-text="'Lesson ' + d"></option>
                                                        </template>
                                                        <option value="__new__">Add New Value…</option>
                                                    </select>
                                                    <input x-show="edit.dayValue === '__new__'" x-cloak
                                                           x-model="edit.dayNew"
                                                           type="number" min="1" max="999" placeholder="Lesson #"
                                                           class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 w-24">
                                                </div>
                                            </div>

                                            {{-- Status / error --}}
                                            <p x-show="edit.message" x-cloak
                                               class="text-sm"
                                               :class="edit.isError ? 'text-red-600' : 'text-green-600'"
                                               x-text="edit.message"></p>

                                            {{-- Action buttons --}}
                                            <div class="flex gap-2 pt-1">
                                                <button type="button"
                                                        @click="saveRelocate(plan)"
                                                        :disabled="edit.saving"
                                                        class="px-4 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-md hover:bg-gray-700 transition-colors disabled:opacity-60">
                                                    <span x-text="edit.saving ? 'Saving…' : 'Save and Update'"></span>
                                                </button>
                                                <button type="button"
                                                        @click="discardEdit()"
                                                        class="px-4 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors">
                                                    Discard Changes
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             COMPARE TWO LESSON PLANS
        ══════════════════════════════════════════════════════════ --}}
        <div class="mt-12" x-data="compareTable(@js($allPlansFlat))">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Compare Two Lesson Plans</h2>
            <p class="text-xs text-gray-500 mb-3">
                Check one plan to filter the table to its family. Check a second plan to see the diff below.
            </p>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{-- Clear checkboxes --}}
                                    <button type="button" @click="clearSelection()"
                                            title="Clear selection"
                                            class="text-gray-400 hover:text-gray-700 text-xs">✕</button>
                                </th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Official</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Lesson</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contributor</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ver.</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="plan in visiblePlans" :key="plan.id">
                                <tr class="hover:bg-blue-50"
                                    :class="{
                                        'bg-blue-50': checkedIds.includes(plan.id),
                                        'opacity-40': filterFamily && !familyMatch(plan)
                                    }">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox"
                                               :value="plan.id"
                                               :checked="checkedIds.includes(plan.id)"
                                               @change="toggleCheck(plan)"
                                               :disabled="!checkedIds.includes(plan.id) && checkedIds.length >= 2"
                                               class="rounded border-gray-300 disabled:opacity-40 cursor-pointer">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span x-show="plan.is_official" class="text-xl font-bold text-gray-900">✓</span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700" x-text="plan.class_name"></td>
                                    <td class="px-3 py-2 text-gray-700 text-center" x-text="plan.grade"></td>
                                    <td class="px-3 py-2 text-gray-700 text-center" x-text="plan.lesson_day"></td>
                                    <td class="px-3 py-2 text-gray-500 text-xs truncate max-w-[120px]" x-text="plan.description || '—'"></td>
                                    <td class="px-3 py-2 text-gray-700 text-xs" x-text="plan.author_name"></td>
                                    <td class="px-3 py-2 text-gray-700 text-center font-mono text-xs" x-text="plan.version"></td>
                                    <td class="px-3 py-2 text-gray-500 text-xs" x-text="plan.updated_at"></td>
                                </tr>
                            </template>
                            <tr x-show="visiblePlans.length === 0">
                                <td colspan="9" class="px-4 py-6 text-center text-gray-400 text-sm">No plans in this family.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Selection status hint --}}
            <p class="mt-2 text-xs text-gray-500"
               x-show="checkedIds.length === 1"
               x-cloak>
                One plan selected. Now check a second plan from the same family to compare.
            </p>

            {{-- Diff result area --}}
            <div x-show="diffLoading" x-cloak class="mt-4 text-sm text-gray-500 italic">Loading diff…</div>
            <div x-show="diffWarning" x-cloak class="mt-4 border border-amber-200 bg-amber-50 rounded-lg p-4 text-sm text-amber-800" x-text="diffWarning"></div>

            <div x-show="diffOps.length > 0" x-cloak class="mt-4 space-y-4">

                {{-- Plan labels --}}
                <div class="text-sm text-gray-700">
                    Comparing
                    <span class="font-medium" x-text="diffPlanB ? diffPlanB.label : ''"></span>
                    (baseline) → <span class="font-medium" x-text="diffPlanA ? diffPlanA.label : ''"></span>
                    (current)
                </div>

                {{-- Summary stats --}}
                <div class="grid grid-cols-3 gap-3 text-center text-sm">
                    <div class="border border-green-200 bg-green-50 rounded-lg p-3">
                        <p class="text-xl font-bold text-green-700" x-text="'+' + (diffSummary.added || 0)"></p>
                        <p class="text-xs text-green-700 uppercase tracking-wider mt-0.5">Lines Added</p>
                    </div>
                    <div class="border border-red-200 bg-red-50 rounded-lg p-3">
                        <p class="text-xl font-bold text-red-700" x-text="'-' + (diffSummary.removed || 0)"></p>
                        <p class="text-xs text-red-700 uppercase tracking-wider mt-0.5">Lines Removed</p>
                    </div>
                    <div class="border border-gray-200 bg-gray-50 rounded-lg p-3">
                        <p class="text-xl font-bold text-gray-700" x-text="diffSummary.changed || 0"></p>
                        <p class="text-xs text-gray-700 uppercase tracking-wider mt-0.5">Lines Changed</p>
                    </div>
                </div>

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
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-xs text-gray-500">
                        Baseline (left) → Current (right)
                    </div>
                    <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                        <div class="font-mono text-xs">
                            <template x-for="(op, i) in diffOps" :key="i">
                                <div class="flex gap-3 px-4 py-0.5"
                                     :class="{
                                         'bg-green-50 text-green-900': op.type === 'add',
                                         'bg-red-50 text-red-900':   op.type === 'remove',
                                         'text-gray-600':             op.type === 'equal'
                                     }">
                                    <span class="select-none w-4 shrink-0 font-bold"
                                          x-text="op.type === 'add' ? '+' : (op.type === 'remove' ? '−' : ' ')"></span>
                                    <span class="whitespace-pre-wrap break-all" x-text="op.line"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Side-by-side diff --}}
                <div x-show="sideBySide" x-cloak class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-xs text-gray-500">
                        Left: baseline &nbsp;|&nbsp; Right: current
                    </div>
                    <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                        <table class="w-full font-mono text-xs border-collapse">
                            <tbody>
                                <template x-for="(row, i) in sideBySideRows" :key="i">
                                    <tr class="border-t border-gray-100">
                                        <td class="px-3 py-0.5 w-1/2 border-r border-gray-200 whitespace-pre-wrap break-all"
                                            :class="{
                                                'bg-red-50 text-red-900':   row.type === 'remove' || row.type === 'change',
                                                'text-gray-600':            row.type === 'equal' || row.type === 'add'
                                            }"
                                            x-text="row.left"></td>
                                        <td class="px-3 py-0.5 w-1/2 whitespace-pre-wrap break-all"
                                            :class="{
                                                'bg-green-50 text-green-900': row.type === 'add' || row.type === 'change',
                                                'text-gray-600':              row.type === 'equal' || row.type === 'remove'
                                            }"
                                            x-text="row.right"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- No differences message --}}
            <div x-show="checkedIds.length === 2 && !diffLoading && !diffWarning && diffOps.length === 0 && diffRan"
                 x-cloak
                 class="mt-4 border border-amber-200 bg-amber-50 rounded-lg p-4 text-sm text-amber-800">
                <p class="font-medium mb-1">These versions have identical content.</p>
                <p>The files are byte-for-byte the same. You can delete the duplicate from the <strong>Lesson Plans</strong> table above — check the box next to the unwanted version and click <strong>Del</strong>.</p>
            </div>

        </div>

    </div>

    {{-- ══ Alpine.js component definitions ══ --}}
    <script>
    // ─── Relocate table ───────────────────────────────────────────────────────
    function relocateTable(allPlansData, classNamesData, gradesData, daysData) {
        return {
            allPlans:      allPlansData,
            classNames:    classNamesData,
            grades:        gradesData,
            days:          daysData,
            expandedId:    null,
            edit:          {},
            showConflict:  false,
            conflictTarget:'',
            pendingPayload: null,

            toggleExpand(plan) {
                if (this.expandedId === plan.id) {
                    this.expandedId = null;
                    this.edit = {};
                    return;
                }
                this.expandedId = plan.id;
                this.edit = {
                    changeClass: false,  classValue: plan.class_name, classNew: '',
                    changeGrade: false,  gradeValue: String(plan.grade),
                    changeDay:   false,  dayValue:   String(plan.lesson_day), dayNew: '',
                    saving:      false,  message:    '',  isError: false,
                };
            },

            discardEdit() {
                this.expandedId = null;
                this.edit = {};
            },

            resolvedClassName() {
                if (!this.edit.changeClass) return null;
                return this.edit.classValue === '__new__' ? this.edit.classNew.trim() : this.edit.classValue;
            },
            resolvedGrade() {
                if (!this.edit.changeGrade) return null;
                return parseInt(this.edit.gradeValue, 10);
            },
            resolvedDay() {
                if (!this.edit.changeDay) return null;
                return this.edit.dayValue === '__new__' ? parseInt(this.edit.dayNew, 10) : parseInt(this.edit.dayValue, 10);
            },

            async saveRelocate(plan, conflictResolution = null) {
                const cn  = this.resolvedClassName();
                const gr  = this.resolvedGrade();
                const day = this.resolvedDay();

                if (!this.edit.changeClass && !this.edit.changeGrade && !this.edit.changeDay) {
                    this.edit.message = 'No changes selected.';
                    this.edit.isError = true;
                    return;
                }
                if (this.edit.changeClass && (!cn || cn.length === 0)) {
                    this.edit.message = 'Please enter a class name.';
                    this.edit.isError = true;
                    return;
                }
                if (this.edit.changeDay && (!day || isNaN(day) || day < 1)) {
                    this.edit.message = 'Please enter a valid lesson number.';
                    this.edit.isError = true;
                    return;
                }

                this.edit.saving  = true;
                this.edit.message = '';
                this.edit.isError = false;

                const payload = {};
                if (cn  !== null) payload.class_name  = cn;
                if (gr  !== null) payload.grade        = gr;
                if (day !== null) payload.lesson_day   = day;
                if (conflictResolution) payload.conflict_resolution = conflictResolution;

                try {
                    const resp = await fetch(`/admin/lesson-plans/${plan.id}/relocate`, {
                        method:  'PATCH',
                        headers: {
                            'Content-Type':  'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]').content,
                            'Accept':        'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const json = await resp.json().catch(() => ({}));

                    if (resp.status === 409 && json.conflict) {
                        // Show conflict modal
                        this.edit.saving   = false;
                        this.pendingPayload = { plan, payload };
                        const targetClass = cn  || plan.class_name;
                        const targetGrade = gr  || plan.grade;
                        const targetDay   = day || plan.lesson_day;
                        this.conflictTarget = `${targetClass} Grade ${targetGrade} Lesson ${targetDay}`;
                        this.showConflict  = true;
                        return;
                    }

                    if (!resp.ok) {
                        this.edit.message = resp.status === 419
                            ? 'Session expired — please refresh the page.'
                            : (json.message || 'Save failed. Please try again.');
                        this.edit.isError = true;
                        this.edit.saving  = false;
                        return;
                    }

                    // Success — update local data row
                    const idx = this.allPlans.findIndex(p => p.id === plan.id);
                    if (idx !== -1) {
                        if (json.class_name !== undefined) this.allPlans[idx].class_name = json.class_name;
                        if (json.grade      !== undefined) this.allPlans[idx].grade      = json.grade;
                        if (json.lesson_day !== undefined) this.allPlans[idx].lesson_day = json.lesson_day;
                        if (json.file_name  !== undefined) this.allPlans[idx].file_name  = json.file_name;
                    }

                    this.edit.message = '✓ Saved successfully.';
                    this.edit.isError = false;
                    this.edit.saving  = false;
                    setTimeout(() => this.discardEdit(), 1500);

                } catch (e) {
                    this.edit.message = 'Network error. Please try again.';
                    this.edit.isError = true;
                    this.edit.saving  = false;
                }
            },

            resolveConflict(resolution) {
                this.showConflict = false;
                if (!this.pendingPayload) return;
                const { plan, payload } = this.pendingPayload;
                this.pendingPayload = null;
                this.saveRelocate(plan, resolution);
            },
        };
    }

    // ─── Compare table ────────────────────────────────────────────────────────
    function compareTable(allPlansData) {
        return {
            allPlans:      allPlansData,
            checkedIds:    [],
            filterFamily:  false,
            sideBySide:    false,
            diffLoading:   false,
            diffRan:       false,
            diffWarning:   '',
            diffOps:       [],
            diffSummary:   {},
            sideBySideRows:[],
            diffPlanA:     null,
            diffPlanB:     null,

            get visiblePlans() {
                if (!this.filterFamily || this.checkedIds.length === 0) {
                    return this.allPlans;
                }
                const anchor = this.allPlans.find(p => p.id === this.checkedIds[0]);
                if (!anchor) return this.allPlans;
                return this.allPlans.filter(p =>
                    p.class_name === anchor.class_name &&
                    p.grade      === anchor.grade      &&
                    p.lesson_day === anchor.lesson_day
                );
            },

            familyMatch(plan) {
                if (this.checkedIds.length === 0) return true;
                const anchor = this.allPlans.find(p => p.id === this.checkedIds[0]);
                if (!anchor) return true;
                return plan.class_name === anchor.class_name &&
                       plan.grade      === anchor.grade      &&
                       plan.lesson_day === anchor.lesson_day;
            },

            toggleCheck(plan) {
                const idx = this.checkedIds.indexOf(plan.id);
                if (idx !== -1) {
                    // Uncheck
                    this.checkedIds.splice(idx, 1);
                    if (this.checkedIds.length === 0) {
                        this.filterFamily = false;
                        this.resetDiff();
                    } else if (this.checkedIds.length === 1) {
                        this.resetDiff();
                    }
                    return;
                }
                if (this.checkedIds.length >= 2) return; // max 2

                this.checkedIds.push(plan.id);

                if (this.checkedIds.length === 1) {
                    // First check: filter to family
                    this.filterFamily = true;
                    this.resetDiff();
                } else {
                    // Second check: run diff
                    this.runDiff();
                }
            },

            clearSelection() {
                this.checkedIds   = [];
                this.filterFamily = false;
                this.resetDiff();
            },

            resetDiff() {
                this.diffOps       = [];
                this.diffSummary   = {};
                this.sideBySideRows= [];
                this.diffWarning   = '';
                this.diffPlanA     = null;
                this.diffPlanB     = null;
                this.diffRan       = false;
            },

            async runDiff() {
                if (this.checkedIds.length !== 2) return;
                this.diffLoading = true;
                this.diffWarning = '';
                this.diffOps     = [];
                this.diffRan     = false;

                try {
                    const resp = await fetch('/admin/compare-plans', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept':       'application/json',
                        },
                        body: JSON.stringify({ plan_a: this.checkedIds[0], plan_b: this.checkedIds[1] }),
                    });

                    const json = await resp.json().catch(() => ({}));

                    if (json.warning) {
                        this.diffWarning = json.warning;
                    } else {
                        this.diffOps        = json.diffOps    || [];
                        this.diffSummary    = json.diffSummary || {};
                        this.sideBySideRows = json.sideBySide  || [];
                        this.diffPlanA      = json.planA || null;
                        this.diffPlanB      = json.planB || null;
                    }
                } catch (e) {
                    this.diffWarning = 'Network error loading diff.';
                } finally {
                    this.diffLoading = false;
                    this.diffRan     = true;
                }
            },
        };
    }
    </script>

</x-layout>
