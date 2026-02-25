<x-layout>
    <x-slot:title>ARES: Lesson Plans — Sign In</x-slot>

    <div class="max-w-sm mx-auto mt-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Sign In</h2>

        <form method="POST" action="{{ route('login') }}" x-data="{ show: false }">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Username
                </label>
                <input type="email" id="email" name="email"
                       value="{{ old('email') }}" required autofocus
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                @error('email')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" id="password" name="password" required
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
            <a href="{{ route('register') }}"
               class="ml-1 text-sm font-medium text-gray-900 hover:text-gray-600 underline">
                Sign Up
            </a>
        </div>
    </div>
</x-layout>
