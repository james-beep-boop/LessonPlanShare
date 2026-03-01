<x-layout>
    <x-slot:title>Teacher Guide — ARES Education</x-slot>

    <div class="max-w-2xl mx-auto" x-data="{ lang: 'en' }">

        {{-- Language Toggle --}}
        <div class="flex gap-2 mb-6">
            <button @click="lang = 'en'"
                    :class="lang === 'en'
                        ? 'bg-gray-900 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-colors">
                English
            </button>
            <button @click="lang = 'sw'"
                    :class="lang === 'sw'
                        ? 'bg-gray-900 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-colors">
                Kiswahili
            </button>
        </div>

        {{-- ══════════════════════════════════════════════════════
             ENGLISH CONTENT
             ══════════════════════════════════════════════════════ --}}
        <div x-show="lang === 'en'">

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

                {{-- 1. Signing In / Signing Up --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">1. Signing In / Signing Up</h2>
                    <p class="mb-3">You need an account to view, download, vote, or upload. There are two separate dialogs — one for existing accounts and one for new ones.</p>

                    <p class="font-medium text-gray-800 mb-2">Signing in (existing account):</p>
                    <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                        <li>At <strong>www.sheql.com</strong>, click <strong>Sign In</strong> (top-right corner)</li>
                        <li>Type your <strong>Teacher Email</strong> and <strong>Password</strong></li>
                        <li>Click <strong>Sign In</strong></li>
                    </ol>

                    <p class="font-medium text-gray-800 mb-2">Creating a new account:</p>
                    <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                        <li>At <strong>www.sheql.com</strong>, click <strong>Sign In</strong> (top-right corner)</li>
                        <li>Click <strong>New User? Sign Up</strong> at the bottom of the dialog</li>
                        <li>Type a <strong>Teacher Name</strong> (the name other teachers will see), your <strong>email address</strong>, and a <strong>password</strong></li>
                        <li>Click <strong>Sign Up</strong></li>
                    </ol>

                    <div class="bg-amber-50 border border-amber-200 rounded-md px-4 py-3 mb-4">
                        <p class="font-semibold text-amber-800 mb-1">Important</p>
                        <p class="text-amber-700">A confirmation email will be sent to you. <strong>You must click the link inside that email</strong> before you can use the site.</p>
                    </div>
                    <p class="font-medium text-gray-800 mb-2">Common problems and easy fixes:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>No email arrived?</strong> Check your spam or junk folder.</li>
                        <li><strong>Still no email?</strong> Try signing in again — the system will resend a fresh verification link.</li>
                        <li><strong>Forgot your password?</strong> Click "Forgot your password?" on the Sign In dialog.</li>
                        <li><strong>Still stuck?</strong> Contact the site administrator for help.</li>
                    </ul>
                    <p class="mt-3 text-gray-600">Once your email is verified, click <strong>Sign In</strong> and enter your email and password each time you visit.</p>
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
                        <li>Click your <strong>Teacher Name</strong> at the top of the page to instantly filter the list to only your own uploaded plans</li>
                    </ul>
                    <p>Click the <strong>View/Edit</strong> button on any row to open that lesson plan.</p>
                </section>

                {{-- 3. Viewing a Lesson Plan --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">3. Viewing a Lesson Plan</h2>
                    <p class="mb-2">On the lesson plan detail page you can:</p>
                    <ul class="list-disc pl-5 space-y-1 mb-3">
                        <li>Read the description and see the full version history in the sidebar</li>
                        <li>Click <strong>View in Google Docs</strong> to open the document in Google's online viewer — no download or Google account needed</li>
                        <li>Click <strong>View in Microsoft Office</strong> to open the document in Microsoft's online viewer — no download or Microsoft account needed</li>
                        <li>Click <strong>Download</strong> to save the file to your device so you can edit it in Word, LibreOffice, or Google Docs</li>
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
                        <li>Click <strong>Upload New Lesson</strong> at the bottom of the dashboard</li>
                        <li>Choose the <strong>Class</strong> and <strong>Lesson Day</strong> number</li>
                        <li>Add a short <strong>description</strong> — optional, but very helpful to others</li>
                        <li>Choose your file (DOC, DOCX, TXT, RTF, or ODT only — maximum 1&nbsp;MB)</li>
                        <li>Click <strong>Upload Lesson Plan</strong></li>
                    </ol>
                    <div class="bg-amber-50 border border-amber-200 rounded-md px-4 py-3 mb-4">
                        <p class="font-semibold text-amber-800 mb-1">If a plan already exists for that Class and Day</p>
                        <p class="text-amber-700 text-sm">A warning dialog will appear with four options: choose a different class name, use the next available lesson day number (shown automatically), archive the existing plan(s) with a deletion timestamp, or cancel.</p>
                    </div>
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
                    <p class="text-gray-600">To see all your plans quickly, click your <strong>Teacher Name</strong> at the top of the page — or tick <strong>Show only my plans</strong> in the filter bar on the dashboard.</p>
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

        </div>{{-- /English --}}


        {{-- ══════════════════════════════════════════════════════
             KISWAHILI CONTENT
             ══════════════════════════════════════════════════════ --}}
        <div x-show="lang === 'sw'" style="display:none">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Mwongozo wa Mwalimu</h1>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors">
                    &larr; Rudi kwenye Dashibodi
                </a>
            </div>

            {{-- Welcome --}}
            <p class="text-gray-600 mb-8 leading-relaxed">
                Karibu kwenye Maktaba ya Maandalio ya Masomo (Lesson Plan Library) ya ARES — maktaba ya pamoja ya bure ambapo walimu wa Kenya wanaweza kupata, kupakua, kutathmini, na kushiriki maandalio ya masomo. Mwongozo huu utakuonyesha jinsi ya kutumia tovuti hatua kwa hatua, ili uweze kunufaika nayo kikamilifu.
            </p>

            <div class="space-y-8 text-gray-700 text-sm leading-relaxed">

                {{-- 1. Kuingia --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">1. Kuingia / Kusajiliwa (Signing In / Signing Up)</h2>
                    <p class="mb-3">Unahitaji akaunti ili kutazama, kupakua, kupiga kura, au kupakia maandalio. Kuna mazungumzo mawili tofauti — moja kwa akaunti zilizopo na moja kwa akaunti mpya.</p>
                    <p class="font-medium text-gray-800 mb-2">Kuingia (akaunti iliyopo):</p>
                    <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                        <li>Kwenye <strong>www.sheql.com</strong>, bonyeza <strong>Sign In</strong> (kona ya juu kulia)</li>
                        <li>Andika <strong>Barua Pepe ya Mwalimu</strong> na <strong>Nywila (Password)</strong></li>
                        <li>Bonyeza <strong>Sign In</strong></li>
                    </ol>
                    <p class="font-medium text-gray-800 mb-2">Kuunda akaunti mpya:</p>
                    <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                        <li>Kwenye <strong>www.sheql.com</strong>, bonyeza <strong>Sign In</strong> (kona ya juu kulia)</li>
                        <li>Bonyeza <strong>New User? Sign Up</strong> chini ya mazungumzo</li>
                        <li>Andika <strong>Jina la Mwalimu</strong> (jina ambalo walimu wengine wataliona), <strong>barua pepe</strong> yako, na <strong>nywila</strong></li>
                        <li>Bonyeza <strong>Sign Up</strong></li>
                    </ol>
                    <div class="bg-amber-50 border border-amber-200 rounded-md px-4 py-3 mb-4">
                        <p class="font-semibold text-amber-800 mb-1">Muhimu</p>
                        <p class="text-amber-700">Utatumiwa barua pepe ya uthibitisho. <strong>Lazima ubonyeze kiungo kilicho ndani ya barua pepe hiyo</strong> kabla ya kuanza kutumia tovuti.</p>
                    </div>
                    <p class="font-medium text-gray-800 mb-2">Matatizo ya kawaida na masuluhisho mepesi:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Barua pepe haijafika?</strong> Angalia folda yako ya "spam" au "junk".</li>
                        <li><strong>Bado hakuna barua pepe?</strong> Jaribu kuingia tena — mfumo utatuma kiungo kipya.</li>
                        <li><strong>Umesahau nywila?</strong> Bonyeza "Forgot your password?" kwenye fomu ya kuingia.</li>
                        <li><strong>Bado umekwama?</strong> Wasiliana na msimamizi wa tovuti kwa usaidizi.</li>
                    </ul>
                    <p class="mt-3 text-gray-600">Pindi barua pepe yako itakapothibitishwa, bonyeza tu <strong>Sign In</strong> kila unapotembelea.</p>
                </section>

                {{-- 2. Kupata Maandalio --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">2. Kupata Maandalio ya Masomo</h2>
                    <p class="mb-3">Baada ya kuingia, utafika kwenye <strong>Dashibodi</strong> — ukurasa wako mkuu. Njia za haraka za kupata unachohitaji:</p>
                    <ul class="list-disc pl-5 space-y-1 mb-3">
                         <li>Bonyeza kichwa cha safu yoyote (Darasa, Siku, Mwandishi, Tathmini, Kusasishwa) ili kupanga orodha</li>
                        <li>Andika kwenye kisanduku cha <strong>Search</strong> (jina la darasa, mada, au jina la mwalimu)</li>
                        <li>Chagua <strong>Darasa (Class)</strong> kutoka kwenye kichujio cha kushuka (dropdown)</li>
                        <li>Weka alama kwenye <strong>Show only latest</strong> (kwenye upau wa kichujio chini ya kisanduku cha utafutaji) — huficha matoleo ya zamani ili uone tu andalio la hivi karibuni la kila darasa/siku</li>
                    </ul>
                    <p>Bonyeza kitufe cha <strong>View/Edit</strong> kwenye safu yoyote ili kufungua andalio hilo la somo.</p>
                </section>

                {{-- 3. Kutazama na Kuhakiki --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">3. Kutazama Andalio la Somo</h2>
                    <p class="mb-2">Kwenye ukurasa wa maelezo ya andalio la somo unaweza:</p>
                    <ul class="list-disc pl-5 space-y-1 mb-3">
                        <li>Soma maelezo na uone historia kamili ya matoleo</li>
                        <li>Bonyeza <strong>View in Google Docs</strong> ili kufungua hati katika kionyeshi cha mtandaoni cha Google — hakuna haja ya kupakua au akaunti ya Google</li>
                        <li>Bonyeza <strong>View in Microsoft Office</strong> ili kufungua hati katika kionyeshi cha mtandaoni cha Microsoft — hakuna haja ya kupakua au akaunti ya Microsoft</li>
                        <li>Bonyeza <strong>Download</strong> ili kuhifadhi faili kwenye kifaa chako</li>
                    </ul>
                    <div class="bg-blue-50 border border-blue-200 rounded-md px-4 py-3 text-blue-800">
                        <strong>Kidokezo:</strong> Lazima ufungue ukurasa wa maelezo angalau mara moja kabla ya vitufe vyako vya kupiga kura kuanza kufanya kazi kwenye dashibodi.
                    </div>
                </section>

                {{-- 4. Kupakua --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">4. Kupakua Andalio la Somo</h2>
                    <p class="mb-2">Kwenye ukurasa wa maelezo ya andalio la somo, bonyeza kitufe cha <strong>Download File</strong>. Faili itapakuliwa ikiwa na jina sanifu, kwa mfano:</p>
                    <p class="font-mono text-xs bg-gray-100 rounded px-3 py-2 mb-3">Mathematics_Day5_v1-2-3.docx</p>
                    <p>Unaweza kuifungua kwa Microsoft Word, Google Docs, au programu yoyote ya kuchakata maneno.</p>
                </section>

                {{-- 5. Kupiga Kura --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">5. Kupiga Kura kwa Maandalio ya Masomo</h2>
                    <p class="mb-2">Kura zako huwasaidia walimu wengine kupata maandalio bora zaidi.</p>
                    <ol class="list-decimal pl-5 space-y-1 mb-3">
                        <li>Fungua ukurasa wowote wa maelezo ya andalio la somo</li>
                        <li>Bonyeza <strong>👍</strong> ikiwa andalio ni zuri, au <strong>👎</strong> ikiwa linahitaji maboresho</li>
                    </ol>
                    <ul class="list-disc pl-5 space-y-1 text-gray-600">
                        <li>Huwezi kupigia kura maandalio yako mwenyewe</li>
                        <li>Kubonyeza kitufe kile kile mara mbili huondoa kura yako</li>
                    </ul>
                </section>

                {{-- 6. Kuhifadhi Maandalio Unayopenda --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">6. Kuhifadhi Maandalio Unayopenda (Favourites)</h2>
                    <p class="mb-2">Kwenye dashibodi, bonyeza <strong>★ nyota</strong> kando ya andalio lolote ili kulihifadhi kama unalopenda. Nyota ya njano inamaanisha limehifadhiwa. Bonyeza tena ili kuliondoa.</p>
                    <p class="text-gray-600">Tumia sehemu ya "favourites" kupata haraka maandalio unayotumia mara kwa mara.</p>
                </section>

                {{-- 7. Kupakia Maandalio Yako --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">7. Kupakia Maandalio Yako Mwenyewe</h2>
                    <p class="mb-3">Kushiriki maandalio yako bora husaidia kila mwalimu. Ili kupakia:</p>
                    <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                        <li>Bonyeza <strong>Upload New Lesson</strong> juu ya ukurasa wowote</li>
                        <li>Chagua <strong>Darasa (Class)</strong> na namba ya <strong>Siku ya Somo (Lesson Day)</strong></li>
                        <li>Ongeza <strong>maelezo</strong> mafupi — si lazima, lakini husaidia sana wengine</li>
                        <li>Chagua faili yako (DOC, DOCX, TXT, RTF, au ODT pekee — isizidi 1 MB)</li>
                        <li>Bonyeza <strong>Upload Lesson Plan</strong></li>
                    </ol>
                    <p class="mb-3">Mfumo utahifadhi faili yako kwa jina sanifu na kukutumia barua pepe ya uthibitisho.</p>
                    <p class="font-medium text-gray-800 mb-1">Jinsi namba za matoleo (versions) zinavyofanya kazi:</p>
                    <ul class="list-disc pl-5 space-y-1 text-gray-600">
                        <li>Kila andalio jipya huanza na <strong>1.0.0</strong></li>
                        <li>Maboresho makubwa → namba ya katikati huongezeka &nbsp;<span class="font-mono text-xs bg-gray-100 rounded px-1">1.0.0 → 1.1.0</span></li>
                    </ul>
                </section>

            </div>

            {{-- Bottom back button --}}
            <div class="mt-10 pt-6 border-t border-gray-200">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-md transition-colors">
                    &larr; Rudi kwenye Dashibodi
                </a>
            </div>

        </div>{{-- /Kiswahili --}}

    </div>

</x-layout>
