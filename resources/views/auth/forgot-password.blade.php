<x-layout>
    <x-slot:title>Reset Password — ARES Education</x-slot>

    <div class="max-w-sm mx-auto mt-12">
        <div class="bg-white border border-gray-200 rounded-lg p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-3">Reset Your Password</h2>

            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                Enter your email address and we'll send you a link to reset your password.
            </p>

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-md mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}" required autofocus
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-gray-900 text-white text-sm font-medium py-2.5 rounded-md
                               hover:bg-gray-700 transition-colors">
                    Send Reset Link
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                <a href="{{ route('login') }}"
                   class="text-sm font-medium text-gray-900 hover:text-gray-600 underline">
                    Back to Sign In
                </a>
            </div>
        </div>
    </div>
</x-layout>
