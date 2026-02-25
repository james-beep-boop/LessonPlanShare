<x-layout>
    <x-slot:title>Set New Password — ARES Education</x-slot>

    <div class="max-w-sm mx-auto mt-12">
        <div class="bg-white border border-gray-200 rounded-lg p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Set New Password</h2>

            <form method="POST" action="{{ route('password.store') }}" x-data="{ showPw: false }">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email', $request->email) }}" required autofocus
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        New Password
                    </label>
                    <div class="relative">
                        <input :type="showPw ? 'text' : 'password'" id="password" name="password" required
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

                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <input :type="showPw ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2 pr-16 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                        <button type="button" @click="showPw = !showPw"
                                class="absolute inset-y-0 right-0 px-3 text-xs text-gray-500 hover:text-gray-700 font-medium"
                                x-text="showPw ? 'Hide' : 'Show'"></button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-gray-900 text-white text-sm font-medium py-2.5 rounded-md
                               hover:bg-gray-700 transition-colors">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</x-layout>
