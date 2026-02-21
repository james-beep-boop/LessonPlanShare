<x-layout>
    <x-slot:title>Create New Version - {{ $lessonPlan->class_name }}</x-slot>

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Create New Version</h1>
        <p class="text-sm text-gray-600 mb-6">
            You are creating a new version based on
            <strong>{{ $lessonPlan->class_name }} Day {{ $lessonPlan->lesson_day }}</strong> (v{{ $lessonPlan->version_number }})
            by {{ $lessonPlan->author->name ?? 'Unknown' }}.
        </p>

        {{-- Download the current version first --}}
        @if ($lessonPlan->file_path)
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <strong>Step 1:</strong> Download the current version, make your improvements, then upload the revised file below.
                </p>
                <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                   class="inline-block mt-2 px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Download v{{ $lessonPlan->version_number }} ({{ $lessonPlan->file_name }})
                </a>
            </div>
        @endif

        <form method="POST" action="{{ route('lesson-plans.update', $lessonPlan) }}" enctype="multipart/form-data"
              class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-5">
            @csrf
            @method('PUT')

            {{-- Class Name --}}
            <div>
                <label for="class_name" class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                <input type="text" name="class_name" id="class_name"
                       value="{{ old('class_name', $lessonPlan->class_name) }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('class_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Lesson Day --}}
            <div>
                <label for="lesson_day" class="block text-sm font-medium text-gray-700 mb-1">Lesson Day *</label>
                <input type="number" name="lesson_day" id="lesson_day"
                       value="{{ old('lesson_day', $lessonPlan->lesson_day) }}" required min="1" max="999"
                       class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('lesson_day') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Author (read-only) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author (this version)</label>
                <p class="text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                    {{ auth()->user()->name }}
                </p>
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="4"
                          placeholder="Describe what you changed or improved..."
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $lessonPlan->description) }}</textarea>
                @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- File Upload --}}
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Revised Lesson Plan File *</label>
                <input type="file" name="file" id="file" required
                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.rtf,.odt,.odp,.ods"
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-xs text-gray-500 mt-1">Upload your revised version. Max 10 MB.</p>
                @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center space-x-3 pt-2">
                <button type="submit"
                        class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                    Upload New Version
                </button>
                <a href="{{ route('lesson-plans.show', $lessonPlan) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>

</x-layout>
