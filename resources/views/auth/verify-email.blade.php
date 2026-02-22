<x-layout>
    <x-slot:title>Verify Your Email — ARES Education</x-slot>

    <div class="max-w-md mx-auto mt-12">
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center">
            <div class="text-gray-400 mb-4">
                <svg class="w-14 h-14 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <h2 class="text-xl font-semibold text-gray-900 mb-3">Check Your Email</h2>

            <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                We've sent a verification email to <strong>{{ auth()->user()->email }}</strong>.
                Please click the confirmation button in that email to activate your account.
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-md mb-4">
                    A new verification link has been sent.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                        class="w-full bg-gray-900 text-white text-sm font-medium py-2.5 rounded-md
                               hover:bg-gray-700 transition-colors mb-3">
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-900 underline">
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</x-layout>
