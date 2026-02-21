<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Lesson Plan Exchange' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Navigation Bar --}}
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                {{-- Left: Brand & Links --}}
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="font-bold text-xl text-indigo-600">
                        Lesson Plan Exchange
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-900' }}">
                            Browse All
                        </a>
                        <a href="{{ route('my-plans') }}"
                           class="text-sm font-medium {{ request()->routeIs('my-plans') ? 'text-indigo-600' : 'text-gray-600 hover:text-gray-900' }}">
                            My Plans
                        </a>
                        <a href="{{ route('lesson-plans.create') }}"
                           class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            + Upload Plan
                        </a>
                    @endauth
                </div>

                {{-- Right: Auth --}}
                <div class="flex items-center space-x-4">
                    @auth
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">Login</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Register</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Upload-success dialog (prominent, dismissible) --}}
    @if (session('upload_success'))
        <div x-data="{ open: true }" x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 text-center">
                <div class="text-green-500 mb-3">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Upload Successful!</h3>
                <p class="text-sm text-gray-600 mb-1">Your lesson plan has been saved as:</p>
                <p class="text-sm font-mono bg-gray-100 rounded px-3 py-2 break-all mb-4">
                    {{ session('upload_filename') }}
                </p>
                <p class="text-xs text-gray-500 mb-4">A confirmation email has been sent to your address.</p>
                <button @click="open = false"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    OK
                </button>
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} Lesson Plan Exchange &mdash; Helping teachers share and improve.
        </div>
    </footer>

</body>
</html>
