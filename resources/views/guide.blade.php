<x-layout>
    <x-slot:title>Teacher Guide — ARES Education</x-slot>

    <div class="max-w-2xl mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Teacher Guide</h1>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors">
                &larr; Back to Dashboard
            </a>
        </div>

        {{-- Welcome --}}
        <p class="text-gray-600 mb-8 leading-relaxed">
            Welcome to the ARES Lesson Plan Library — a free shared library where Kenyan teachers can find, download, rate, and share lesson plans. This guide will show you how to use the site step by step, so you can get the most out of it.
        </p>

        <div class="space-y-8 text-gray-700 text-sm leading-relaxed">

            {{-- 1. Signing In --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">1. Signing In</h2>
                <p class="mb-3">You need an account to view, download, vote, or upload. Here is how:</p>
                <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                    <li>At <strong>www.sheql.com</strong> click <strong>Sign In</strong> (top-right corner)</li>
                    <li>Type your <strong>Teacher Name</strong> (the name other teachers will see), your <strong>email address</strong>, and a <strong>password</strong></li>
                    <li>Click <strong>Sign In</strong></li>
                </ol>
                <div class="bg-amber-50 border border-amber-200 rounded-md px-4 py-3 mb-4">
                    <p class="font-semibold text-amber-800 mb-1">Important</p>
                    <p class="text-amber-700">A confirmation email will be sent to you. <strong>You must click the link inside that email</strong> before you can use the site.</p>
                </div>
                <p class="font-medium text-gray-800 mb-2">Common problems and easy fixes:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>No email arrived?</strong> Check your spam or junk folder.</li>
                    <li><strong>Still no email?</strong> Try signing in again — the system will send a fresh link.</li>
                    <li><strong>Forgot your password?</strong> Click "Forgot your password?" on the sign-in form.</li>
                    <li><strong>Still stuck?</strong> Contact the site administrator for help.</li>
                </ul>
                <p class="mt-3 text-gray-600">Once your email is verified, just click <strong>Sign In</strong> each time you visit.</p>
            </section>

            {{-- 2. Finding Lesson Plans --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">2. Finding Lesson Plans</h2>
                <p class="mb-3">After signing in you land on the <strong>Dashboard</strong> — your main screen. Quick ways to find what you need:</p>
                <ul class="list-disc pl-5 space-y-1 mb-3">
                     <li>Click any column heading (Class, Day&nbsp;#, Author, Rating, Updated) to sort the list</li>
                    <li>Type in the <strong>Search</strong> box (class name, topic, or teacher name)</li>
                    <li>Choose a <strong>Class</strong> from the dropdown filter</li>
                    <li>Tick <strong>Show only latest</strong> (in the filter bar below the search box) — hides older versions so you see only the most recent plan for each class/day</li>
                </ul>
                <p>Click the <strong>View/Edit/Vote</strong> button on any row to open that lesson plan.</p>
            </section>

            {{-- 3. Viewing and Previewing --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">3. Viewing and Previewing a Lesson Plan</h2>
                <p class="mb-2">On the lesson plan detail page you can:</p>
                <ul class="list-disc pl-5 space-y-1 mb-3">
                    <li>Read the description and see the full version history</li>
                    <li>Click <strong>Preview File</strong> to read the document right inside the website — no download needed</li>
                    <li>Click <strong>Print / Save PDF</strong> to print the plan or save it as a PDF</li>
                </ul>
                <div class="bg-blue-50 border border-blue-200 rounded-md px-4 py-3 text-blue-800">
                    <strong>Tip:</strong> You must open the detail page at least once before your vote buttons become active on the dashboard.
                </div>
            </section>

            {{-- 4. Downloading --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">4. Downloading a Lesson Plan</h2>
                <p class="mb-2">On the lesson plan detail page, click the <strong>Download File</strong> button. The file downloads with a clear standard name, for example:</p>
                <p class="font-mono text-xs bg-gray-100 rounded px-3 py-2 mb-3">Mathematics_Day5_v1-2-3.docx</p>
                <p>You can open it in Microsoft Word, Google Docs, or any word processor.</p>
            </section>

            {{-- 5. Voting --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">5. Voting on Lesson Plans</h2>
                <p class="mb-2">Your votes help other teachers find the best plans.</p>
                <ol class="list-decimal pl-5 space-y-1 mb-3">
                    <li>Open any lesson plan detail page</li>
                    <li>Click <strong>👍</strong> if the plan is good, or <strong>👎</strong> if it needs improvement</li>
                </ol>
                <ul class="list-disc pl-5 space-y-1 text-gray-600">
                    <li>You cannot vote on your own plans</li>
                    <li>Clicking the same button twice removes your vote</li>
                </ul>
            </section>

            {{-- 6. Saving Favourite Plans --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">6. Saving Favourite Plans</h2>
                <p class="mb-2">On the dashboard, click the <strong>★ star</strong> next to any plan to save it as a favourite. A yellow star means it is saved. Click again to remove it.</p>
                <p class="text-gray-600">Use favourites to quickly find the plans you use most often.</p>
            </section>

            {{-- 7. Uploading Your Own Plans --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">7. Uploading Your Own Lesson Plans</h2>
                <p class="mb-3">Sharing your best plans helps every teacher. To upload:</p>
                <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                    <li>Click <strong>Upload New Lesson</strong> at the top of any page</li>
                    <li>Choose the <strong>Class</strong> and <strong>Lesson Day</strong> number</li>
                    <li>Add a short <strong>description</strong> — optional, but very helpful to others</li>
                    <li>Choose your file (DOC, DOCX, TXT, RTF, or ODT only — maximum 1&nbsp;MB)</li>
                    <li>Click <strong>Upload Lesson Plan</strong></li>
                </ol>
                <p class="mb-3">The system saves your file with a clean standard name and sends you a confirmation email.</p>
                <p class="font-medium text-gray-800 mb-1">How version numbers work:</p>
                <ul class="list-disc pl-5 space-y-1 text-gray-600">
                    <li>Every new plan starts at <strong>1.0.0</strong></li>
                    <li>A big improvement → the middle number goes up &nbsp;<span class="font-mono text-xs bg-gray-100 rounded px-1">1.0.0 → 1.1.0</span></li>
                    <li>A small fix → the last number goes up &nbsp;<span class="font-mono text-xs bg-gray-100 rounded px-1">1.0.0 → 1.0.1</span></li>
                </ul>
            </section>

            {{-- 8. Managing Your Plans --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">8. Managing Your Own Plans</h2>
                <p class="mb-2">You can delete any plan that <strong>you</strong> uploaded. Open the plan and click the red <strong>Delete</strong> button.</p>
                <div class="bg-red-50 border border-red-200 rounded-md px-4 py-3 text-red-800 mb-3">
                    <strong>Warning:</strong> Deleted plans are gone permanently and cannot be recovered.
                </div>
                <p class="text-gray-600">To see all your plans, type your teacher name in the <strong>Search</strong> box on the dashboard.</p>
            </section>

            {{-- 9. For Administrators --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">9. For Administrators</h2>
                <p class="mb-2">If you are an administrator, you will see an <strong>Admin</strong> link at the top. Administrators can:</p>
                <ul class="list-disc pl-5 space-y-1 mb-2">
                    <li>Delete any lesson plan</li>
                    <li>Delete or verify any user account</li>
                    <li>Grant administrator access to other teachers</li>
                </ul>
                <p class="text-gray-600">Please use these tools carefully to keep the library clean and safe for everyone.</p>
            </section>

            {{-- Need Help? --}}
            <section class="bg-gray-50 border border-gray-200 rounded-lg px-6 py-5">
                <h2 class="text-base font-semibold text-gray-900 mb-2">Need Help?</h2>
                <p class="mb-2">Contact the site administrator if you have trouble with:</p>
                <ul class="list-disc pl-5 space-y-1 text-gray-600 mb-4">
                    <li>Receiving your verification email</li>
                    <li>Uploading or downloading a file</li>
                    <li>Any other problem on the site</li>
                </ul>
                <p>Thank you for being part of the ARES teacher community. By sharing and rating lesson plans together, we are making teaching better for all our students.</p>
            </section>

        </div>

        {{-- Bottom back button --}}
        <div class="mt-10 pt-6 border-t border-gray-200">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors">
                &larr; Back to Dashboard
            </a>
        </div>

    </div>

</x-layout>
