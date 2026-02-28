<x-layout>
    <x-slot:title>Preview — {{ $lessonPlan->name }} — ARES Education</x-slot>

    {{-- Alpine.js x-data wraps the whole page so the Refresh Viewer button in the
         header row can share state with the iframe src binding below. --}}
    @php
        $fileUrl       = asset('storage/' . $lessonPlan->file_path);
        $viewerUrlBase = 'https://docs.google.com/gview?url=' . urlencode($fileUrl) . '&embedded=true&t=';
        $initialTs     = time();
    @endphp

    <div class="max-w-5xl mx-auto"
         x-data="{
             viewerBase: @js($viewerUrlBase),
             ts: {{ $initialTs }},
             refresh() { this.ts = Date.now(); }
         }">

        {{-- ── Header: plan info + four uniform action buttons ── --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-4 gap-3">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Preview</p>
                <h1 class="text-lg font-bold text-gray-900">{{ $lessonPlan->class_name }} — Day {{ $lessonPlan->lesson_day }}</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Version {{ $lessonPlan->semantic_version }}
                    &middot; by {{ $lessonPlan->author->name ?? 'Anonymous' }}
                    &middot; {{ $lessonPlan->file_name }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Click &ldquo;Refresh Viewer&rdquo; if the lesson plan does not appear in the viewer.
                </p>
            </div>

            {{-- Five uniform buttons on one row --}}
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button @click="refresh()" type="button"
                        class="px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-md border border-gray-300 transition-colors">
                    ↻ Refresh Viewer
                </button>
                {{-- Print/PDF: opens raw file URL — PDFs open in browser native viewer (Ctrl+P / ⌘+P to print); other formats download --}}
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener"
                   title="Opens raw document — Ctrl+P / ⌘+P to print (PDF opens in browser; other formats download)"
                   class="px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-md border border-gray-300 transition-colors">
                    Print / PDF
                </a>
                <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                   class="px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-md border border-gray-300 transition-colors">
                    Download File
                </a>
                <a href="{{ route('lesson-plans.show', $lessonPlan) }}"
                   class="px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-md border border-gray-300 transition-colors">
                    Back to Details
                </a>
                <a href="{{ route('dashboard') }}"
                   class="px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-md border border-gray-300 transition-colors">
                    Home
                </a>
            </div>
        </div>

        {{-- ── Document Viewer ── --}}
        {{-- Uses Google Docs Viewer to render .doc/.docx files in an iframe.
             The file must be publicly accessible via URL for this to work.
             Clicking "Refresh Viewer" updates ts = Date.now(), forcing a new &t=
             query param that busts Google's cache without a full page reload. --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
            {{-- The gview toolbar contains an "open in new tab" icon that leads to a blank page.
                 CSS overlays don't reliably intercept touch events over cross-origin iframes on
                 iOS Safari. Instead, shift the iframe up by the toolbar height (≈44 px) so the
                 toolbar scrolls out of view and is clipped by the wrapper's overflow: hidden.
                 Document content starts below the toolbar, so nothing useful is clipped. --}}
            <div style="overflow: hidden; height: 75vh; min-height: 500px;">
                <iframe :src="viewerBase + ts"
                        class="w-full bg-white"
                        style="height: calc(75vh + 44px); min-height: 544px; margin-top: -44px;"
                        frameborder="0"
                        loading="lazy"
                        title="Document Preview: {{ $lessonPlan->file_name }}">
                </iframe>
            </div>

            {{-- Fallback note --}}
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <p class="text-xs text-gray-400">
                    Preview powered by Google Docs Viewer. If the document does not load, click ↻ Refresh Viewer above. If that does not work, download the file.
                </p>
                <a href="{{ route('lesson-plans.download', $lessonPlan) }}"
                   class="text-xs text-gray-900 font-medium hover:text-gray-600 underline flex-shrink-0">
                    Download {{ $lessonPlan->file_name }}
                </a>
            </div>
        </div>

    </div>

</x-layout>
