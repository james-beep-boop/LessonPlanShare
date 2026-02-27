<x-layout>
    <x-slot:title>Guide — ARES Education</x-slot>

    <div class="max-w-2xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">How to Use This Site</h1>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors">
                &larr; Back to Dashboard
            </a>
        </div>

        <div class="prose prose-sm max-w-none space-y-6 text-gray-700 leading-relaxed">

            {{-- Registration & Login --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 mb-2">1. Registration &amp; Login</h2>
                <p>
                    To access the site, click <strong>Sign In</strong> at the top right. You will be asked for
                    a <strong>Teacher Name</strong> (any unique display name you choose),
                    a <strong>valid email address</strong>, and a <strong>password</strong>.
                    A confirmation email will be sent to that address — you must click the link in it
                    before you can log in. New accounts and returning accounts use the same form.
                </p>
            </section>

            {{-- Version numbering --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 mb-2">2. Version Numbering</h2>
                <p>
                    Documents follow a <strong>Version.Major.Minor</strong> numbering scheme.
                    When a lesson plan is first uploaded it is <strong>1.0.0</strong>.
                    Each major revision increments the middle number; each minor revision increments
                    the last number. For example, a plan with four major and one minor revision
                    is numbered <strong>1.4.1</strong>.
                </p>
            </section>

            {{-- Viewing & downloading --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 mb-2">3. Viewing &amp; Downloading</h2>
                <p>
                    Any registered user who has verified their email may view or download any document.
                    Click the <strong>View/Edit/Vote</strong> button on the dashboard to open the
                    plan detail page, where you will find Download and Preview buttons.
                </p>
            </section>

            {{-- Uploading --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 mb-2">4. Uploading a Document</h2>
                <p>
                    Use the <strong>Upload New Lesson</strong> button in the header to submit a new lesson plan
                    or a revised version of an existing one. You must specify:
                </p>
                <ul class="list-disc pl-5 mt-1 space-y-1">
                    <li>The <strong>class</strong> the plan is for</li>
                    <li>The <strong>lesson day</strong> number</li>
                    <li>Whether it is a <strong>major or minor revision</strong> of an existing plan</li>
                </ul>
                <p class="mt-2">
                    Accepted file types: DOC, DOCX, TXT, RTF, ODT.
                    The file is automatically renamed to a standard format when saved.
                </p>
            </section>

            {{-- Deleting --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 mb-2">5. Deleting a Document</h2>
                <p>
                    You may delete any lesson plan that <strong>you personally uploaded</strong>.
                    Open the plan detail page and use the Delete button. This action is permanent.
                </p>
            </section>

            {{-- Voting --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 mb-2">6. Voting</h2>
                <p>
                    You may <strong>upvote (▲) or downvote (▼)</strong> any lesson plan except your own.
                    You must view the plan detail page at least once before your vote buttons become active.
                    Voting the same direction a second time removes your vote.
                </p>
            </section>

            {{-- Administrators --}}
            <section>
                <h2 class="text-base font-semibold text-gray-900 mb-2">7. Administrators</h2>
                <p>
                    Administrators have access to an <strong>Admin panel</strong> where they can delete
                    any document or any user account, verify user emails manually,
                    and grant administrator privileges to other users.
                </p>
            </section>

        </div>

        <div class="mt-10 pt-6 border-t border-gray-200">
            <a href="{{ route('dashboard') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition-colors border border-gray-300">
                &larr; Back to Dashboard
            </a>
        </div>

    </div>

</x-layout>
