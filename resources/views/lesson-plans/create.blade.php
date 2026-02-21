<x-layout>
    <x-slot:title>Upload New Lesson Plan</x-slot>

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Upload a New Lesson Plan</h1>

        <form method="POST" action="{{ route('lesson-plans.store') }}" enctype="multipart/form-data"
              class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-5">
            @csrf

            {{-- Class Name --}}
            <div>
                <label for="class_name" class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                <input type="text" name="class_name" id="class_name" value="{{ old('class_name') }}" required
                       placeholder="e.g., AP Biology, Algebra II, World History"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('class_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Lesson Day --}}
            <div>
                <label for="lesson_day" class="block text-sm font-medium text-gray-700 mb-1">Lesson Day *</label>
                <input type="number" name="lesson_day" id="lesson_day" value="{{ old('lesson_day') }}"
                       required min="1" max="999"
                       placeholder="e.g., 1, 2, 15..."
                       class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('lesson_day') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Author (read-only, auto-filled from login) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                <p class="text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Automatically set to your login name.</p>
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="4"
                          placeholder="Briefly describe the lesson plan, objectives, grade level, etc."
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Generated Name Preview --}}
            <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                <p class="text-xs font-medium text-blue-700 mb-1">Document Name (auto-generated)</p>
                <p class="text-xs text-blue-600">
                    Your document will be named using the format:<br>
                    <code class="bg-blue-100 px-1 rounded">{ClassName}_Day{N}_{YourName}_{UTC-Timestamp}</code>
                </p>
            </div>

            {{-- File Upload --}}
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Lesson Plan File *</label>
                <input type="file" name="file" id="file" required
                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.rtf,.odt,.odp,.ods"
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="text-xs text-gray-500 mt-1">Max 10 MB. Accepted: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, RTF, ODT, ODP, ODS.</p>
                @error('file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center space-x-3 pt-2">
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Upload Lesson Plan
                </button>
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
            </div>
        </form>
    </div>

</x-layout>
