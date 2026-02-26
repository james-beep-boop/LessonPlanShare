<x-layout>
    <x-slot:title>Admin Panel — ARES Education</x-slot>

    <div class="max-w-6xl mx-auto">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Admin Panel</h1>
            <p class="text-sm text-gray-500 mt-1">Manage lesson plans and users. Deletions are permanent.</p>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             LESSON PLANS TABLE
        ══════════════════════════════════════════════════════════ --}}
        <div class="mb-12">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Lesson Plans ({{ $plans->total() }})</h2>

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
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    {{-- Select-all checkbox --}}
                                    <input type="checkbox"
                                           onclick="document.querySelectorAll('.plan-cb').forEach(cb => cb.checked = this.checked)"
                                           class="rounded border-gray-300">
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Delete</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Day #</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Author</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ver.</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">File</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Updated</th>
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
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ $plan->class_name }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-center">{{ $plan->lesson_day }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-xs">{{ $plan->author_name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700 text-center">{{ $plan->version_number }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs font-mono">{{ $plan->file_name }}</td>
                                    <td class="px-3 py-2 text-gray-500 text-xs">{{ $plan->updated_at->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-400">No lesson plans.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($plans->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        {{ $plans->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             REGISTERED USERS TABLE
        ══════════════════════════════════════════════════════════ --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Registered Users ({{ $users->total() }})</h2>

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
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox"
                                           onclick="document.querySelectorAll('.user-cb').forEach(cb => cb.checked = this.checked)"
                                           class="rounded border-gray-300">
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Delete</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Teacher Name</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Verified</th>
                                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Registered</th>
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
                                                    Delete
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
                                    <td class="px-3 py-2 text-center">
                                        @if ($u->is_admin)
                                            <span class="text-blue-600 text-xs font-medium">Yes</span>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-500 text-xs">{{ $u->created_at->format('M j, Y g:ia') }}</td>
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
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-400">No users.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-layout>
