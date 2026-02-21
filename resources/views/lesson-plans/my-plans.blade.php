<x-layout>
    <x-slot:title>My Lesson Plans</x-slot>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Lesson Plans</h1>
            <p class="text-sm text-gray-600 mt-1">Plans you have uploaded or revised.</p>
        </div>
        <a href="{{ route('lesson-plans.create') }}"
           class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
            + Upload New Plan
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Document Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Day #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Version</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Rating</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Updated</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
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
                            <td class="px-4 py-3">
                                <x-vote-buttons :score="$plan->vote_score" :readonly="true" />
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $plan->updated_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    @if ($plan->file_path)
                                        <a href="{{ route('lesson-plans.download', $plan) }}"
                                           class="text-indigo-600 hover:text-indigo-800 text-xs">Download</a>
                                    @endif
                                    <form method="POST" action="{{ route('lesson-plans.destroy', $plan) }}"
                                          onsubmit="return confirm('Delete this plan?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                You haven't uploaded any lesson plans yet.
                                <a href="{{ route('lesson-plans.create') }}" class="text-indigo-600 hover:text-indigo-800">Upload your first one!</a>
                            </td>
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

</x-layout>
