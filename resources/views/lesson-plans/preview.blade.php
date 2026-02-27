<x-layout>
    <x-slot:title>Preview — {{ $lessonPlan->name }} — ARES Education</x-slot>

    <div class="max-w-5xl mx-auto">

        {{-- ── Header bar with "Preview" label, plan name, and actions ── --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Preview</p>
                <h1 class="text-lg font-bold text-gray-900">{{ $lessonPlan->class_name }} — Day {{ $lessonPlan->lesson_day }}</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Version {{ $lessonPlan->semantic_version }}
                    &middot; by {{ $lessonPlan->author->name ?? 'Unknown' }}
                    &middot; {{ $lessonPlan->file_name }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                   class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors">
                    Download File
                </a>
                <a href="{{ route('lesson-plans.show', $lessonPlan) }}"
                   class="px-4 py-2 bg-gray-100 text-gray-900 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                    &larr; Back to Details
                </a>
                <a href="{{ route('dashboard') }}"
                   class="text-sm text-gray-500 hover:text-gray-900">Home</a>
            </div>
        </div>

        {{-- ── Document Viewer ── --}}
        {{-- Uses Google Docs Viewer to render .doc/.docx files in an iframe.
             The file must be publicly accessible via URL for this to work.
             Falls back to a friendly message if the preview fails to load. --}}
        @php
            // Build the full public URL to the stored file
            $fileUrl = asset('storage/' . $lessonPlan->file_path);
            // Google Docs Viewer URL — renders Office docs in the browser.
            // The &t= timestamp busts Google's cache so the viewer reloads the
            // document on every visit (without it the iframe silently goes blank
            // on second and subsequent views of the same document).
            $viewerUrl = 'https://docs.google.com/gview?url=' . urlencode($fileUrl) . '&embedded=true&t=' . time();
        @endphp

        <div class="border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
            {{-- Viewer iframe --}}
            <iframe src="{{ $viewerUrl }}"
                    class="w-full bg-white"
                    style="height: 75vh; min-height: 500px;"
                    frameborder="0"
                    loading="lazy"
                    title="Document Preview: {{ $lessonPlan->file_name }}">
            </iframe>

            {{-- Fallback note below the viewer --}}
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <p class="text-xs text-gray-400">
                    Preview powered by Google Docs Viewer. If the document does not load, refresh the page. If that does not work, download the file.
                </p>
                <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                   class="text-xs text-gray-900 font-medium hover:text-gray-600 underline flex-shrink-0">
                    Download {{ $lessonPlan->file_name }}
                </a>
            </div>
        </div>

    </div>

</x-layout>
