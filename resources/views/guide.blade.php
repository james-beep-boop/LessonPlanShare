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
        </div>
        {{-- ══════════════════════════════════════════════════════
             KISWAHILI CONTENT
             ══════════════════════════════════════════════════════ --}}
        <div x-show="lang === 'sw'">
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
                Karibu katika Maktaba ya Mipango ya Masomo ya ARES — maktaba ya bure inayoshirikiwa ambapo walimu wa Kenya wanaweza kupata, kupakua, kukadiria na kushiriki mipango ya masomo. Mwongozo huu utakuonyesha jinsi ya kutumia tovuti hatua kwa hatua, ili uweze kuipata faida kubwa zaidi.
            </p>
            <div class="space-y-8 text-gray-700 text-sm leading-relaxed">
                {{-- 1. Kuingia / Kujisajili --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">1. Kuingia / Kujisajili</h2>
                    <p class="mb-3">Unahitaji akaunti ili kuona, kupakua, kupiga kura au kupakia. Kuna vidirisha viwili tofauti — kimoja kwa akaunti zilizopo na kingine kwa mpya.</p>
                    <p class="font-medium text-gray-800 mb-2">Kuingia (akaunti iliyopo):</p>
                    <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                        <li>Katika <strong>www.sheql.com</strong>, bonyeza <strong>Sign In</strong> (kona ya juu kulia)</li>
                        <li>Andika <strong>Teacher Email</strong> yako na <strong>Password</strong></li>
                        <li>Bonyeza <strong>Sign In</strong></li>
                    </ol>
                    <p class="font-medium text-gray-800 mb-2">Kuunda akaunti mpya:</p>
                    <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                        <li>Katika <strong>www.sheql.com</strong>, bonyeza <strong>Sign In</strong> (kona ya juu kulia)</li>
                        <li>Bonyeza <strong>New User? Sign Up</strong> chini ya dirisha</li>
                        <li>Andika <strong>Teacher Name</strong> (jina ambalo walimu wengine wataona), anwani yako ya <strong>email address</strong>, na <strong>password</strong></li>
                        <li>Bonyeza <strong>Sign Up</strong></li>
                    </ol>
                    <div class="bg-amber-50 border border-amber-200 rounded-md px-4 py-3 mb-4">
                        <p class="font-semibold text-amber-800 mb-1">Muhimu</p>
                        <p class="text-amber-700">Barua pepe ya uthibitisho itatumwa kwako. <strong>Lazima ubonyeze kiungo kilicho ndani ya barua hiyo pepe</strong> kabla ya kutumia tovuti.</p>
                    </div>
                    <p class="font-medium text-gray-800 mb-2">Matatizo ya kawaida na marekebisho rahisi:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Hakuna barua pepe iliyofika?</strong> Angalia folda yako ya spam au junk.</li>
                        <li><strong>Bado hakuna barua pepe?</strong> Jaribu kuingia tena — mfumo utatuma kiungo kipya cha uthibitisho.</li>
                        <li><strong>Umesahau password yako?</strong> Bonyeza "Forgot your password?" kwenye dirisha la Sign In.</li>
                        <li><strong>Bado umekwama?</strong> Wasiliana na msimamizi wa tovuti kwa msaada.</li>
                    </ul>
                    <p class="mt-3 text-gray-600">Baada ya barua pepe yako kuthibitishwa, bonyeza <strong>Sign In</strong> na uingize barua pepe na password kila unapotembelea.</p>
                </section>
                {{-- 2. Kupata Mipango ya Masomo --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">2. Kupata Mipango ya Masomo</h2>
                    <p class="mb-3">Baada ya kuingia utafika kwenye <strong>Dashibodi</strong> — skrini yako kuu. Njia za haraka za kupata unachohitaji:</p>
                    <ul class="list-disc pl-5 space-y-1 mb-3">
                        <li>Bonyeza kichwa chochote cha safu (Darasa, Siku&nbsp;#, Mwandishi, Ukadiriaji, Imeboreshwa) ili kupanga orodha</li>
                        <li>Andika kwenye kisanduku cha <strong>Search</strong> (jina la darasa, mada, au jina la mwalimu)</li>
                        <li>Chagua <strong>Darasa</strong> kutoka kwenye dropdown ya kuchuja</li>
                        <li>Tiki <strong>Show only latest</strong> (katika upau wa kuchuja chini ya kisanduku cha kutafuta) — inaficha matoleo ya zamani ili uone tu mpango wa hivi karibuni kwa kila darasa/siku</li>
                        <li>Bonyeza <strong>Teacher Name</strong> yako juu ya ukurasa ili kuchuja orodha mara moja kuonyesha mipango uliyopakia wewe mwenyewe pekee</li>
                    </ul>
                    <p>Bonyeza kitufe cha <strong>View/Edit</strong> kwenye safu yoyote ili kufungua mpango huo wa somo.</p>
                </section>
                {{-- 3. Kuona Mpango wa Somo --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">3. Kuona Mpango wa Somo</h2>
                    <p class="mb-2">Kwenye ukurasa wa maelezo ya mpango wa somo unaweza:</p>
                    <ul class="list-disc pl-5 space-y-1 mb-3">
                        <li>Soma maelezo na uone historia kamili ya matoleo kwenye upande</li>
                        <li>Bonyeza <strong>View in Google Docs</strong> ili kufungua waraka katika kivinjari cha mtandaoni cha Google — hakuna kupakua au akaunti ya Google inayohitajika</li>
                        <li>Bonyeza <strong>View in Microsoft Office</strong> ili kufungua waraka katika kivinjari cha mtandaoni cha Microsoft — hakuna kupakua au akaunti ya Microsoft inayohitajika</li>
                        <li>Bonyeza <strong>Download</strong> ili kuhifadhi faili kwenye kifaa chako ili uweze kuihariri katika Word, LibreOffice, au Google Docs</li>
                    </ul>
                    <div class="bg-blue-50 border border-blue-200 rounded-md px-4 py-3 text-blue-800">
                        <strong>Kidokezo:</strong> Lazima ufungue ukurasa wa maelezo angalau mara moja kabla ya vitufe vya kupiga kura kuwa hai kwenye dashibodi.
                    </div>
                </section>
                {{-- 4. Kupakua Mpango wa Somo --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">4. Kupakua Mpango wa Somo</h2>
                    <p class="mb-2">Kwenye ukurasa wa maelezo ya mpango wa somo, bonyeza kitufe cha <strong>Download File</strong>. Faili itapakuliwa na jina safi la kawaida, kwa mfano:</p>
                    <p class="font-mono text-xs bg-gray-100 rounded px-3 py-2 mb-3">Mathematics_Day5_v1-2-3.docx</p>
                    <p>Unaweza kuifungua katika Microsoft Word, Google Docs, au kichakataji chochote cha maneno.</p>
                </section>
                {{-- 5. Kupiga Kura --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">5. Kupiga Kura kwenye Mipango ya Masomo</h2>
                    <p class="mb-2">Kura zako zinawasaidia walimu wengine kupata mipango bora.</p>
                    <ol class="list-decimal pl-5 space-y-1 mb-3">
                        <li>Fungua ukurasa wowote wa maelezo ya mpango wa somo</li>
                        <li>Bonyeza <strong>👍</strong> kama mpango ni mzuri, au <strong>👎</strong> kama unahitaji kuboreshwa</li>
                    </ol>
                    <ul class="list-disc pl-5 space-y-1 text-gray-600">
                        <li>Huwezi kupiga kura kwenye mipango yako mwenyewe</li>
                        <li>Kubonyeza kitufe kile kile mara mbili huondoa kura yako</li>
                    </ul>
                </section>
                {{-- 6. Kuhifadhi Mipango Unayopenda --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">6. Kuhifadhi Mipango Unayopenda</h2>
                    <p class="mb-2">Kwenye dashibodi, bonyeza <strong>★ nyota</strong> karibu na mpango wowote ili kuuhifadhi kama unayopenda. Nyota ya manjano inamaanisha umeihifadhi. Bonyeza tena ili kuiondoa.</p>
                    <p class="text-gray-600">Tumia mipango unayopenda ili upate haraka mipango unayoitumia mara nyingi.</p>
                </section>
                {{-- 7. Kupakia Mipango Yako Mwenyewe --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">7. Kupakia Mipango Yako Mwenyewe ya Masomo</h2>
                    <p class="mb-3">Kushiriki mipango yako bora kunawasaidia kila mwalimu. Ili kupakia:</p>
                    <ol class="list-decimal pl-5 space-y-1.5 mb-4">
                        <li>Bonyeza <strong>Upload New Lesson</strong> chini ya dashibodi</li>
                        <li>Chagua <strong>Darasa</strong> na nambari ya <strong>Lesson Day</strong></li>
                        <li>Ongeza <strong>maelezo mafupi</strong> — si lazima, lakini yanawasaidia sana wengine</li>
                        <li>Chagua faili yako (DOC, DOCX, TXT, RTF, au ODT pekee — kiwango cha juu 1&nbsp;MB)</li>
                        <li>Bonyeza <strong>Upload Lesson Plan</strong></li>
                    </ol>
                    <div class="bg-amber-50 border border-amber-200 rounded-md px-4 py-3 mb-4">
                        <p class="font-semibold text-amber-800 mb-1">Ikiwa mpango tayari upo kwa Darasa na Siku hiyo</p>
                        <p class="text-amber-700 text-sm">Dirisha la onyo litaonekana na chaguzi nne: chagua jina tofauti la darasa, tumia nambari inayofuata ya siku ya somo (inayoonyeshwa kiotomatiki), weka mipango iliyopo kwenye kumbukumbu na muhuri wa kufuta, au ghairi.</p>
                    </div>
                    <p class="mb-3">Mfumo utahifadhi faili yako kwa jina safi la kawaida na utakutumia barua pepe ya uthibitisho.</p>
                    <p class="font-medium text-gray-800 mb-1">Jinsi nambari za toleo zinavyofanya kazi:</p>
                    <ul class="list-disc pl-5 space-y-1 text-gray-600">
                        <li>Kila mpango mpya huanza na <strong>1.0.0</strong></li>
                        <li>Kuboresha kubwa → nambari ya kati inaongezeka &nbsp;<span class="font-mono text-xs bg-gray-100 rounded px-1">1.0.0 → 1.1.0</span></li>
                        <li>Kurekebisha kidogo → nambari ya mwisho inaongezeka &nbsp;<span class="font-mono text-xs bg-gray-100 rounded px-1">1.0.0 → 1.0.1</span></li>
                    </ul>
                </section>
                {{-- 8. Kusimamia Mipango Yako --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-1 mb-3">8. Kusimamia Mipango Yako</h2>
                    <p class="mb-2">Unaweza kufuta mpango wowote ulio<strong>upakia wewe</strong>. Fungua mpango na ubonyeze kitufe chekundu cha <strong>Delete</strong>.</p>
                    <div class="bg-red-50 border border-red-200 rounded-md px-4 py-3 text-red-800 mb-3">
                        <strong>Onyo:</strong> Mipango iliyofutwa inaondoka kabisa na haiwezi kurejeshwa.
                    </div>
                    <p class="text-gray-600">Ili kuona mipango yako yote haraka, bonyeza <strong>Teacher Name</strong> yako juu ya ukurasa — au tiki <strong>Show only my plans</strong> katika upau wa kuchuja kwenye dashibodi.</p>
                </section>
                {{-- Unahitaji Msaada? --}}
                <section class="bg-gray-50 border border-gray-200 rounded-lg px-6 py-5">
                    <h2 class="text-base font-semibold text-gray-900 mb-2">Unahitaji Msaada?</h2>
                    <p class="mb-2">Wasiliana na msimamizi wa tovuti kama una shida na:</p>
                    <ul class="list-disc pl-5 space-y-1 text-gray-600 mb-4">
                        <li>Kupokea barua pepe yako ya uthibitisho</li>
                        <li>Kupakia au kupakua faili</li>
                        <li>Tatizo lingine lolote kwenye tovuti</li>
                    </ul>
                    <p>Asante kwa kuwa sehemu ya jumuiya ya walimu wa ARES. Kwa kushiriki na kukadiria mipango ya masomo pamoja, tunafanya ufundishaji uwe bora zaidi kwa wanafunzi wetu wote.</p>
                </section>
            </div>
        </div>
    </div>
</x-layout>
