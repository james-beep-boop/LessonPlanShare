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

                {{-- Right: hamburger (mobile <768px) + horizontal nav (desktop ≥768px) --}}
                <div class="pt-2 relative" x-data="{ menuOpen: false }">

                    {{-- ── Hamburger button — visible on mobile only ── --}}
                    <button @click="menuOpen = !menuOpen"
                            class="md:hidden inline-flex items-center justify-center w-10 h-10
                                   text-gray-500 hover:text-gray-900 hover:bg-gray-100
                                   rounded-md transition-colors"
                            :aria-expanded="menuOpen.toString()"
                            aria-label="Open navigation menu">
                        {{-- Hamburger icon --}}
                        <svg x-show="!menuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        {{-- Close (X) icon --}}
                        <svg x-show="menuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    {{-- ── Desktop nav — hidden on mobile, horizontal row on md+ ── --}}
                    <div class="hidden md:flex items-center gap-5">
                        @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                            <span class="text-lg text-gray-600">{{ auth()->user()->name }}</span>
                        @endif

                        @if(auth()->check() && auth()->user()->is_admin)
                            <a href="{{ route('admin.index') }}"
                               class="text-lg font-medium {{ request()->routeIs('admin.*') ? 'text-gray-900 underline underline-offset-4' : 'text-gray-500 hover:text-gray-900' }}">
                                Admin
                            </a>
                        @endif

                        <a href="{{ route('guide') }}"
                           class="text-lg font-medium {{ request()->routeIs('guide') ? 'text-gray-900 underline underline-offset-4' : 'text-gray-500 hover:text-gray-900' }}">
                            Guide
                        </a>

                        @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="text-lg text-gray-500 hover:text-gray-900 underline">
                                    Sign Out
                                </button>
                            </form>
                        @else
                            <button @click="$dispatch('open-auth-modal')"
                                    class="text-lg font-medium text-gray-900 hover:text-gray-600 cursor-pointer">
                                Sign In
                            </button>
                        @endif
                    </div>

                    {{-- ── Mobile dropdown — appears below hamburger, dismissed on outside click ── --}}
                    <div x-show="menuOpen" x-cloak
                         @click.away="menuOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 top-full mt-2 w-52 bg-white border border-gray-200
                                rounded-lg shadow-lg py-2 z-20 md:hidden">

                        {{-- Teacher name header (verified users only) --}}
                        @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                            <div class="px-4 py-2 text-sm font-medium text-gray-900 border-b border-gray-100 mb-1">
                                {{ auth()->user()->name }}
                            </div>
                        @endif

                        @if(auth()->check() && auth()->user()->is_admin)
                            <a href="{{ route('admin.index') }}"
                               class="block px-4 py-3 text-sm font-medium transition-colors
                                      {{ request()->routeIs('admin.*') ? 'text-gray-900 bg-gray-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
                               @click="menuOpen = false">
                                Admin
                            </a>
                        @endif

                        <a href="{{ route('guide') }}"
                           class="block px-4 py-3 text-sm font-medium transition-colors
                                  {{ request()->routeIs('guide') ? 'text-gray-900 bg-gray-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}"
                           @click="menuOpen = false">
                            Guide
                        </a>

                        <div class="border-t border-gray-100 mt-1 pt-1">
                            @if(auth()->check() && auth()->user()->hasVerifiedEmail())
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-left px-4 py-3 text-sm font-medium
                                                   text-red-600 hover:text-red-700 hover:bg-red-50 transition-colors">
                                        Sign Out
                                    </button>
                                </form>
                            @else
                                <button @click="$dispatch('open-auth-modal'); menuOpen = false"
                                        class="w-full text-left px-4 py-3 text-sm font-medium
                                               text-gray-900 hover:bg-gray-50 transition-colors cursor-pointer">
                                    Sign In
                                </button>
                            @endif
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </header>

    {{-- ──────────────────────────────────────────────────────────
         Auth modals: Sign In + Sign Up (two separate Alpine dialogs)
         Sign In: email + password, errors in 'login' named bag.
         Sign Up: Teacher Name + email + password, errors in 'register' named bag.
         Single x-data scope manages both; @click.stop on inner box + @click on
         the full-screen wrapper closes on backdrop without @click.away cross-firing.
    ────────────────────────────────────────────────────────────── --}}
    @if(!auth()->check() || !auth()->user()->hasVerifiedEmail())
    <div x-data="{
             signIn: {{ $errors->login->any() ? 'true' : 'false' }},
             signUp: {{ $errors->register->any() ? 'true' : 'false' }}
         }"
         @open-auth-modal.window="signIn = true; signUp = false"
         @open-signup-modal.window="signUp = true; signIn = false"
         x-cloak>

        {{-- ── Sign In dialog ── --}}
        <div x-show="signIn" x-transition
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-40"
             @click="signIn = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 relative" @click.stop>

                {{-- Close button --}}
                <button @click="signIn = false"
                        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <h2 class="text-xl font-semibold text-gray-900 mb-5 text-center">Sign In</h2>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Teacher Email --}}
                    <div class="mb-4">
                        <label for="signin-email" class="block text-sm font-medium text-gray-700 mb-1">
                            Teacher Email
                        </label>
                        <input type="email" id="signin-email" name="email"
                               value="{{ old('email') }}" required autofocus autocomplete="email"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                        @error('email', 'login')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-6" x-data="{ show: false }">
                        <label for="signin-password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" id="signin-password" name="password" required
                                   autocomplete="current-password"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 pr-16 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                            <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 px-3 text-xs text-gray-500 hover:text-gray-700 font-medium"
                                    x-text="show ? 'Hide' : 'Show'"></button>
                        </div>
                        @error('password', 'login')
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

                {{-- Switch to Sign Up --}}
                <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                    <button type="button" @click="signIn = false; signUp = true"
                            class="text-sm font-medium text-gray-900 hover:text-gray-600 underline cursor-pointer">
                        New User? Sign Up
                    </button>
                </div>

            </div>
        </div>

        {{-- ── Sign Up dialog ── --}}
        <div x-show="signUp" x-transition
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-40"
             @click="signUp = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 relative" @click.stop>

                {{-- Close button --}}
                <button @click="signUp = false"
                        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <h2 class="text-xl font-semibold text-gray-900 mb-5 text-center">Sign Up</h2>

                <form method="POST" action="{{ route('register.store') }}">
                    @csrf

                    {{-- Teacher Name --}}
                    <div class="mb-4">
                        <label for="signup-name" class="block text-sm font-medium text-gray-700 mb-1">
                            Teacher Name <span class="font-normal text-gray-400">(choose anything unique)</span>
                        </label>
                        <input type="text" id="signup-name" name="name"
                               value="{{ old('name') }}" required autocomplete="name"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                        @error('name', 'register')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Teacher Email --}}
                    <div class="mb-4">
                        <label for="signup-email" class="block text-sm font-medium text-gray-700 mb-1">
                            Teacher Email
                        </label>
                        <input type="email" id="signup-email" name="email"
                               value="{{ old('email') }}" required autocomplete="email"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                        @error('email', 'register')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-6" x-data="{ show: false }">
                        <label for="signup-password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" id="signup-password" name="password" required
                                   autocomplete="new-password"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 pr-16 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                            <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 px-3 text-xs text-gray-500 hover:text-gray-700 font-medium"
                                    x-text="show ? 'Hide' : 'Show'"></button>
                        </div>
                        @error('password', 'register')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full bg-gray-900 text-white text-sm font-medium py-2.5 rounded-md
                                   hover:bg-gray-700 transition-colors">
                        Sign Up
                    </button>
                </form>

                {{-- Switch to Sign In --}}
                <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                    <button type="button" @click="signUp = false; signIn = true"
                            class="text-sm font-medium text-gray-900 hover:text-gray-600 underline cursor-pointer">
                        Already have an account? Sign In
                    </button>
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
            @php
                $appVersion = trim(@file_get_contents(storage_path('app/version.txt')) ?: 'dev');
            @endphp
            <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
                <span>Kenya Lesson Plan Repository version <span class="font-mono text-xs">{{ $appVersion }}</span> &copy; {{ date('Y') }} ARES Education</span>
                <span class="hidden sm:inline">&mdash;</span>
                <span>Lesson Plans are licensed under
                    <a href="https://creativecommons.org/licenses/by-sa/4.0/" rel="license" target="_blank"
                       class="underline hover:text-gray-600">CC&nbsp;BY-SA&nbsp;4.0</a>
                </span>
                {{-- CC BY SA symbols: inline SVG circles, no external CDN --}}
                <span class="inline-flex items-center gap-0.5"
                      aria-label="Creative Commons Attribution ShareAlike 4.0"
                      title="Creative Commons Attribution-ShareAlike 4.0 International">
                    <svg width="18" height="18" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                        <circle cx="10" cy="10" r="9" fill="none" stroke="currentColor" stroke-width="1.5"/>
                        <text x="50%" y="55%" text-anchor="middle" dominant-baseline="middle"
                              font-size="6.5" font-weight="bold" fill="currentColor">CC</text>
                    </svg>
                    <svg width="18" height="18" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                        <circle cx="10" cy="10" r="9" fill="none" stroke="currentColor" stroke-width="1.5"/>
                        <text x="50%" y="55%" text-anchor="middle" dominant-baseline="middle"
                              font-size="6.5" font-weight="bold" fill="currentColor">BY</text>
                    </svg>
                    <svg width="18" height="18" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                        <circle cx="10" cy="10" r="9" fill="none" stroke="currentColor" stroke-width="1.5"/>
                        <text x="50%" y="55%" text-anchor="middle" dominant-baseline="middle"
                              font-size="6.5" font-weight="bold" fill="currentColor">SA</text>
                    </svg>
                </span>
            </div>
        </div>
    </footer>

</body>
</html>
