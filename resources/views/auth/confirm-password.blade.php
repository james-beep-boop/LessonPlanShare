<x-layout>
    <x-slot:title>ARES: Lesson Plans — Confirm Password</x-slot>

    <div class="max-w-sm mx-auto mt-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Confirm Password</h2>
        <p class="text-sm text-gray-600 mb-6">
            This is a secure area. Please confirm your password before continuing.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-transparent">
                @error('password')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-gray-900 text-white text-sm font-medium py-2.5 rounded-md
                           hover:bg-gray-700 transition-colors">
                Confirm
            </button>
        </form>
    </div>
</x-layout>
