<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ARES: Lesson Plans' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-white min-h-screen text-gray-900">

    {{-- ──────────────────────────────────────────────────────────
         Header: ARES Education branding + Sign In / username
    ────────────────────────────────────────────────────────────── --}}
    <header class="border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <div class="flex items-start justify-between">
                {{-- Left: Logo + Branding --}}
                <a href="{{ route('dashboard') }}" class="inline-block">
                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                        ARES Education
                    </h1>
                    <p class="text-base sm:text-lg text-gray-500 mt-1">
                        Kenya Lesson Plan Repository
                    </p>
                </a>

                {{-- Right: username, Stats, Sign Out (or Sign In for guests) --}}
                {{-- Only show username/Sign Out for fully verified users. Unverified
                     users are kept in session so "Resend" works, but they appear as
                     guests in the UI — no name, no Sign Out, Sign In modal available. --}}
                <div class="flex items-center pt-2 space-x-5">
                    @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                        <span class="text-base sm:text-lg text-gray-600 hidden sm:inline">{{ auth()->user()->name }}</span>
                    @endif

                    {{-- Stats link — visible to all visitors --}}
                    <a href="{{ route('stats') }}"
                       class="text-base sm:text-lg font-medium {{ request()->routeIs('stats') ? 'text-gray-900 underline underline-offset-4' : 'text-gray-500 hover:text-gray-900' }}">
                        Stats
                    </a>

                    @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="text-base sm:text-lg text-gray-500 hover:text-gray-900 underline">
                                Sign Out
                            </button>
                        </form>
                    @else
                        <button
                            x-data
                            @click="$dispatch('open-auth-modal', { mode: 'login' })"
                            class="text-base sm:text-lg font-medium text-gray-900 hover:text-gray-600 cursor-pointer">
                            Sign In
                        </button>
                    @endif
                </div>
            </div>

            {{-- Navigation links (only when signed in AND verified) --}}
            @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                <nav class="mt-4 flex flex-wrap gap-4 sm:gap-6 text-sm">
                    <a href="{{ route('dashboard') }}"
                       class="font-medium {{ request()->routeIs('dashboard') ? 'text-gray-900 underline underline-offset-4' : 'text-gray-500 hover:text-gray-900' }}">
                        Browse All
                    </a>
                    <a href="{{ route('my-plans') }}"
                       class="font-medium {{ request()->routeIs('my-plans') ? 'text-gray-900 underline underline-offset-4' : 'text-gray-500 hover:text-gray-900' }}">
                        My Plans
                    </a>
                    <a href="{{ route('lesson-plans.create') }}"
                       class="font-medium text-gray-500 hover:text-gray-900">
                        + Upload Plan
                    </a>
                </nav>
            @endif
        </div>
    </header>

    {{-- ──────────────────────────────────────────────────────────
         Sign In / Sign Up modal dialog (Alpine.js)
         Two panels: 'login' and 'register', toggled by buttons.
    ────────────────────────────────────────────────────────────── --}}
    @if(!auth()->check() || !auth()->user()->hasVerifiedEmail())
    <div x-data="{
            open: {{ $errors->any() ? 'true' : 'false' }},
            mode: '{{ old('_auth_mode', 'login') }}'
         }"
         @open-auth-modal.window="open = true; mode = $event.detail.mode || 'login'"
         x-cloak>

        {{-- Backdrop --}}
        <div x-show="open" x-transition.opacity
             class="fixed inset-0 z-40 bg-black bg-opacity-40"
             @click="open = false"></div>

        {{-- Dialog --}}
        <div x-show="open" x-transition
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 relative"
                 @click.away="open = false">

                {{-- Close button --}}
                <button @click="open = false"
                        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                {{-- ── Login panel ── --}}
                <div x-show="mode === 'login'">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Sign In</h2>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <input type="hidden" name="_auth_mode" value="login">

                        <div class="mb-4">
                            <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1">
                                Username
                            </label>
                            <input type="email" id="login-email" name="email"
                                   value="{{ old('email') }}" required autofocus
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                            @error('email')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6" x-data="{ show: false }">
                            <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'" id="login-password" name="password" required
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 pr-16 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                                <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-0 px-3 text-xs text-gray-500 hover:text-gray-700 font-medium"
                                        x-text="show ? 'Hide' : 'Show'"></button>
                            </div>
                            @error('password')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full bg-gray-900 text-white text-sm font-medium py-2.5 rounded-md
                                       hover:bg-gray-700 transition-colors">
                            Sign In
                        </button>

                        <div class="mt-3 text-center">
                            <a href="{{ route('password.request') }}"
                               class="text-xs text-gray-500 hover:text-gray-900 underline">
                                Forgot your password?
                            </a>
                        </div>
                    </form>

                    <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                        <span class="text-sm text-gray-500">New User?</span>
                        <button @click="mode = 'register'"
                                class="ml-1 text-sm font-medium text-gray-900 hover:text-gray-600 underline">
                            Sign Up
                        </button>
                    </div>
                </div>

                {{-- ── Register panel ── --}}
                <div x-show="mode === 'register'" x-data="{ showPw: false }">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Create Account</h2>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <input type="hidden" name="_auth_mode" value="register">

                        <div class="mb-4">
                            <label for="reg-email" class="block text-sm font-medium text-gray-700 mb-1">
                                Username <span class="font-normal text-gray-400">(must be a valid email)</span>
                            </label>
                            <input type="email" id="reg-email" name="email"
                                   value="{{ old('email') }}" required
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                            @error('email')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="reg-password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <div class="relative">
                                <input :type="showPw ? 'text' : 'password'" id="reg-password" name="password" required
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 pr-16 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                                <button type="button" @click="showPw = !showPw"
                                        class="absolute inset-y-0 right-0 px-3 text-xs text-gray-500 hover:text-gray-700 font-medium"
                                        x-text="showPw ? 'Hide' : 'Show'"></button>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="reg-password-confirm" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <input :type="showPw ? 'text' : 'password'" id="reg-password-confirm" name="password_confirmation" required
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 pr-16 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                                <button type="button" @click="showPw = !showPw"
                                        class="absolute inset-y-0 right-0 px-3 text-xs text-gray-500 hover:text-gray-700 font-medium"
                                        x-text="showPw ? 'Hide' : 'Show'"></button>
                            </div>
                            @error('password')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full bg-gray-900 text-white text-sm font-medium py-2.5 rounded-md
                                       hover:bg-gray-700 transition-colors">
                            Sign Up
                        </button>
                    </form>

                    <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                        <span class="text-sm text-gray-500">Already have an account?</span>
                        <button @click="mode = 'login'"
                                class="ml-1 text-sm font-medium text-gray-900 hover:text-gray-600 underline">
                            Sign In
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- ──────────────────────────────────────────────────────────
         Upload-success dialog
    ────────────────────────────────────────────────────────────── --}}
    @if (session('upload_success'))
        <div x-data="{ open: true }" x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 text-center">
                <div class="text-green-500 mb-3">
                    <svg class="w-14 h-14 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Upload Successful</h3>
                <p class="text-sm text-gray-600 mb-1">Your lesson plan has been saved as:</p>
                <p class="text-sm font-mono bg-gray-50 rounded px-3 py-2 break-all mb-4 border border-gray-200">
                    {{ session('upload_filename') }}
                </p>
                <p class="text-xs text-gray-500 mb-4">A confirmation email has been sent to your address.</p>
                <button @click="open = false"
                        class="px-5 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    OK
                </button>
            </div>
        </div>
    @endif

    {{-- ──────────────────────────────────────────────────────────
         Flash Messages
    ────────────────────────────────────────────────────────────── --}}
    @if (session('success'))
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif
    @if (session('status'))
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md text-sm">
                {{ session('status') }}
            </div>
        </div>
    @endif

    {{-- ──────────────────────────────────────────────────────────
         Main Content
    ────────────────────────────────────────────────────────────── --}}
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    {{-- ──────────────────────────────────────────────────────────
         Footer
    ────────────────────────────────────────────────────────────── --}}
    <footer class="border-t border-gray-200 mt-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-400">
            &copy; {{ date('Y') }} ARES Education &mdash; Kenya Lesson Plan Repository
        </div>
    </footer>

</body>
</html>
