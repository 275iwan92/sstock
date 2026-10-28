{{-- REMARK
 Tujuan       : Technical Guide Stoku (HTML, English): flow besar sistem, siklus dokumen (termasuk Stok Opname), buku besar stok, model data, akses dan sesi, tambah cepat, pesanan dan lead time, konvensi, operasional (pasang, konfigurasi, tes).
                Halaman dokumentasi murni, jadi memakai HTML biasa (kelas sstock-guide di resources/css/app.css), bukan komponen x-dl.
 Sumber hulu  : kode (StockDocuments, StockLedger, Modules, routes/web.php) dan HANDOVER.md; ubah halaman ini bila perilaku itu berubah.
 Output       : tab Help (menu profil > Help & support) di Layer Shell.
 Dipakai oleh : route help.technical-guide (hanya Administrator).
 Status       : Aktif.
--}}
@extends('layout', ['title' => 'Technical Guide', 'bodyClass' => 'sstock-bare'])
@section('body')
@include('help.tabs', ['active' => 'tg'])
<div class="sstock-guide">
    <nav class="g-toc" aria-label="Contents">
        <strong>Technical Guide</strong>
        <a href="#overview">1. System overview</a>
        <a href="#big-flow">2. The big flow</a>
        <a href="#business">3. Business flow</a>
        <a href="#lifecycle">4. Document lifecycle</a>
        <a href="#ledger">5. Stock ledger</a>
        <a href="#data">6. Data model</a>
        <a href="#access">7. Access and security</a>
        <a href="#screens">8. Dashboard and reports</a>
        <a href="#conventions">9. Development conventions</a>
        <a href="#operations">10. Operations</a>
        <a class="g-toc-sub" href="#install">Install steps</a>
        <a class="g-toc-sub" href="#testing">Testing</a>
        <a href="#decisions">11. Key decisions and why</a>
        <a href="#open">12. Open items</a>
    </nav>

    <main class="g-body">
        <h1>Stoku Technical Guide</h1>
        <p class="g-lead">How Stoku works from login to report: where stock is entered, how it is posted, who may do what, and where each rule lives in the code. Administrator access only.</p>

        <h2 id="overview">1. System overview</h2>
        <p>Stoku is a simple inventory application. <b>Master data</b> is defined once, <b>stock documents</b> record every change, the <b>ledger</b> holds the balances, and <b>reports plus the dashboard</b> read the result.</p>
        <table class="g-table">
            <thead><tr><th>Layer</th><th>What it is</th><th>Where</th></tr></thead>
            <tbody>
                <tr><td>Stack</td><td>Laravel 13, PHP 8.3 or newer (<code>"php": "^8.3"</code>), MySQL 8 (tests: in-memory SQLite), Vite 8 + Tailwind 4 (Node 20.19+ or 22.12+)</td><td><code>composer.json</code>, <code>package.json</code>, <code>vite.config.js</code></td></tr>
                <tr><td>UI</td><td>DevLib components (<code>x-dl::*</code>, package <code>jo/devlib</code>, a Composer path repository at <code>../devlib</code>); screens are declarations, not hand-built markup</td><td><code>resources/views</code>, <code>config/devlib.php</code></td></tr>
                <tr><td>Translation</td><td>Screen labels are written in English and translated to Indonesian by an app dictionary</td><td><code>app/Support/Indonesian.php</code>, <code>lang/id</code></td></tr>
                <tr><td>Routing and guards</td><td>One route file; middleware <code>auth</code>, <code>active</code>, <code>module</code> run in that order on every logged-in URL</td><td><code>routes/web.php</code>, <code>EnsureActive</code>, <code>EnsureModuleAccess</code></td></tr>
                <tr><td>Business logic</td><td>Services: document lifecycle (<code>StockDocuments</code>), stock ledger (<code>StockLedger</code>), valuation (<code>StockValuation</code>), Stok Opname (<code>StockCounts</code>, <code>CountSchedule</code>), re-order orders and lead time (<code>ReorderOrders</code>, <code>LeadTimes</code>), incoming units (<code>ProductUnits</code>), sample data (<code>SampleData</code>)</td><td><code>app/Services</code></td></tr>
                <tr><td>Data</td><td>Master tables, documents, ledger</td><td><code>database/migrations</code></td></tr>
            </tbody>
        </table>

        <h2 id="big-flow">2. The big flow</h2>
        @include('help.diagram', ['name' => 'tg-system-flow', 'caption' => 'System flow: from login to reports. StockLedger is the only writer of stock.'])
        <p class="g-note">A confirmed Receiving adds its quantities to the open re-order records of the products it contains. <code>ReorderOrders::reconcile</code> allocates them FIFO and closes a record only when its <code>qty</code> is reached. Cancelling that Receiving recomputes the records, so an order that is no longer complete reopens.</p>

        <h3>Product and batch model</h3>
        @include('help.diagram', ['name' => 'tg-product-batch', 'caption' => 'Product and batch model: tables, document lines, FEFO allocation and the per-product reorder point.'])

        <h2 id="business">3. Business flow</h2>
        <ol class="g-steps">
            <li><b>Set up master data.</b> Categories, locations, units with conversions, suppliers, departments, recipients, items. Items are two-level: a <b>product</b> (<code>products</code>, stable code <code>{CATEGORY PREFIX, 3 chars}-{4-digit sequence}</code> from <code>Product::nextCode</code>, never contains a date) and its <b>batches</b> (<code>items</code>, one per expiry date, batch code <code>{product code}-{YYMMDD}</code>; one undated batch with the product code when the product does not expire). Code, category and <code>tracks_expiry</code> are locked once saved; any user may add a product, only a Supervisor edits parameters (propagated to the batches by <code>Product::propagate</code>). Supplier, Recipient, Department, Category, Unit and Location dropdowns carry a quick-add plus button (section 7). The system supplier <b>Kontan</b> (cash or unknown supplier) always exists.</li>
            <li><b>Opening balance.</b> Supervisor only and optional: the first Receiving is never turned into an opening balance. The date is chosen on the form (not after today) and becomes <code>moved_at</code>. Confirm is rejected by <code>StockLedger::post</code> for a batch that already has any stock movement, a reversal row included (use Adjustment instead). The Stock Card reads the opening balance as an <code>OPENING</code> row when the report period starts on or before that date, otherwise it is folded into the opening balance of the report.</li>
            <li><b>Receiving.</b> <code>supplier_id</code> is required (an active supplier; <b>Kontan</b> for cash purchases). A line names a product and, for <code>tracks_expiry</code> products, an expiry date (required): <code>Product::batchFor()</code> finds or creates the batch (unused batches of a deleted or edited draft are purged). A Receiving line may use another unit; it is converted to the item's stock unit by <code>UnitConverter</code>, which looks first at the product's own <b>incoming units</b> (<code>product_units</code>: <code>1 {unit} = {factor} {products.unit}</code>, stock unit implied as 1) and only then at the global pair in <code>unit_conversions</code> (fallback, never deleted). Issuance and Adjustment are always in the stock unit: no unit picker, a posted <code>unit</code> is ignored, and no <code>entered_*</code> is written (old rows that have them reopen through <code>qty</code>). <code>ProductUnits::options()</code> gives the row its choices (stock unit, own units, global factors for other units); <code>docs/pack-units.blade.php</code> replaces <code>cellRaw</code> / <code>refresh</code> of the <code>lines</code> component on the page (no DevLib change) to list only those units per row and to show the live "= 60 pcs" before Confirm; the server remains the only calculation that counts (<code>StockDocuments::toStockUnit</code>, <code>Decimal</code> bcmath). <code>StockDocuments::assertConversions()</code> collects every product + unit that has neither factor, flashes <code>missing_conversions</code> (<code>product_id, from, to, items</code>) and rejects the save; the form opens the "missing factor" modal (<code>docs/conversion-modal</code>) which posts each pair to <code>docs.conversion</code>, creating the PRODUCT's incoming unit (not a global conversion), then the user saves again.</li>
            <li><b>Issuance.</b> A recipient is required. Stock may never go negative. A line names a batch; a quantity above that batch's stock spills over to the other usable batches of the same product (earliest expiry first, FEFO; <code>StockDocuments::allocate</code>). Picking a later-expiring batch while an earlier one has stock only warns (<code>fefoWarnings</code>), it never blocks.</li>
            <li><b>Adjustment.</b> The line quantity is the difference <code>counted_qty - system_qty</code>. It needs Supervisor approval (single level; multi-level authority is deliberately not used in the stock app, it belongs to purchasing and sales).</li>
            <li><b>Reports.</b> Most have a from / to date filter (Expiry uses a days window instead). Prices and values are visible to Supervisors only.</li>
        </ol>

        <h2 id="lifecycle">4. Document lifecycle</h2>
        <p>Four document types share one controller (<code>StockDocumentController</code>, routes <code>docs/{type}</code> with type <code>opening | receiving | issuance | adjustment</code>) and one service (<code>StockDocuments</code>). The controller reads <code>_action</code> from the form button: <code>save, confirm, delete, cancel, approve, reject</code>. Document numbers are <code>PREFIX-YYMM-NNNN</code> (OPN, RCV, ISS, ADJ), sequenced per type per month. Numbering is race-safe: the column <code>doc_no</code> is unique and <code>StockDocuments::createNumbered</code> reads the last number, tries to insert, and retries up to 5 times when the unique index rejects a collision. Batch creation follows the same idea (<code>Product::batchFor</code> uses <code>createOrFirst</code> on the unique batch code).</p>

        <h3>Opening balance, Receiving, Issuance</h3>
        @include('help.diagram', ['name' => 'tg-doc-lifecycle', 'caption' => 'State diagram of Opening Balance, Receiving and Issuance documents.'])
        <table class="g-table">
            <thead><tr><th>From</th><th>Action</th><th>To</th><th>Stock effect</th></tr></thead>
            <tbody>
                <tr><td>(new)</td><td>Save</td><td><span class="g-st g-grey">draft</span></td><td>None</td></tr>
                <tr><td>draft</td><td>Delete</td><td>(removed)</td><td>None</td></tr>
                <tr><td>draft</td><td>Confirm</td><td><span class="g-st g-green">confirmed</span></td><td>Posted to the ledger in one DB transaction</td></tr>
                <tr><td>confirmed</td><td>Cancel by Supervisor/Admin</td><td><span class="g-st g-red">cancelled</span></td><td>Reversed (new reversal rows)</td></tr>
                <tr><td>confirmed</td><td>Cancel by Data Entry (reason required)</td><td><span class="g-st g-yellow">cancel_pending</span></td><td>None yet</td></tr>
                <tr><td>cancel_pending</td><td>Supervisor Approve</td><td><span class="g-st g-red">cancelled</span></td><td>Reversed</td></tr>
                <tr><td>cancel_pending</td><td>Supervisor Reject, or requester withdraws</td><td><span class="g-st g-green">confirmed</span></td><td>None</td></tr>
            </tbody>
        </table>

        <h3>Adjustment</h3>
        @include('help.diagram', ['name' => 'tg-adjustment-lifecycle', 'caption' => 'State diagram of an Adjustment document.'])
        <table class="g-table">
            <thead><tr><th>From</th><th>Action</th><th>To</th><th>Stock effect</th></tr></thead>
            <tbody>
                <tr><td>(new)</td><td>Save</td><td><span class="g-st g-grey">draft</span></td><td>None</td></tr>
                <tr><td>draft</td><td>Confirm (submit)</td><td><span class="g-st g-yellow">approval</span></td><td>None yet</td></tr>
                <tr><td>approval</td><td>Supervisor Approve</td><td><span class="g-st g-green">approved</span></td><td>Posted to the ledger</td></tr>
                <tr><td>approval</td><td>Supervisor Reject</td><td><span class="g-st g-red">rejected</span></td><td>None</td></tr>
                <tr><td>approval</td><td>Withdraw (Cancel)</td><td><span class="g-st g-red">cancelled</span></td><td>None (nothing was posted)</td></tr>
                <tr><td>approved</td><td>Cancel by Supervisor/Admin</td><td><span class="g-st g-red">cancelled</span></td><td>Reversed</td></tr>
                <tr><td>approved</td><td>Cancel by Data Entry</td><td><span class="g-st g-yellow">cancel_pending</span></td><td>None yet; Approve cancels, Reject returns to approved</td></tr>
            </tbody>
        </table>

        <h3>Stock count (Stok Opname)</h3>
        @include('help.diagram', ['name' => 'tg-stock-count-lifecycle', 'caption' => 'State diagram of a Stok Opname (stock count).'])
        <p>A routine physical count of many batches at once. It is separate from the ad-hoc Adjustment above and does not change it. Module <code>SO</code> (group Transaksi, minimum role Data Entry, guarded route prefix <code>stock-counts.</code>). Logic: <code>App\Services\StockCounts</code>; screens: <code>StockCountController</code>, views <code>stock-counts/*</code>; print data: <code>App\Support\StockCountPrint</code> (<code>x-dl::print-sheet</code>, A4 landscape).</p>
        <table class="g-table">
            <thead><tr><th>From</th><th>Action (who)</th><th>To</th><th>Stock effect</th></tr></thead>
            <tbody>
                <tr><td>(new)</td><td><code>start()</code> (Supervisor)</td><td><span class="g-st g-yellow">counting</span></td><td>None. Snapshot: one <code>stock_count_lines</code> row per active batch with stock &gt; 0 in scope (<code>system_qty</code>, <code>unit_cost</code> = average cost at <code>cutoff_at</code>). Only ONE count may be counting / review at a time</td></tr>
                <tr><td>counting</td><td><code>saveLine()</code> (module RW), <code>addItem()</code></td><td>counting</td><td>None. <code>counted_qty</code> null = not counted yet, 0 = empty; <code>skipped</code> needs a note. Blind count: <code>system_qty</code> is never sent to the browser (lookup <code>item_count</code> also omits stock)</td></tr>
                <tr><td>counting</td><td><code>finish()</code> (module RW)</td><td><span class="g-st g-yellow">review</span></td><td>None. Refused while any line is empty or skipped without a note</td></tr>
                <tr><td>review</td><td><code>reviewLine()</code> (Supervisor): <code>recount_qty</code>, <code>reason</code></td><td>review</td><td>None. Final result = <code>recount_qty</code> if set, else <code>counted_qty</code></td></tr>
                <tr><td>review</td><td><code>approve()</code> (Supervisor)</td><td><span class="g-st g-green">done</span></td><td>One approved ADJ document (<code>reason</code> = "Stok Opname SO-..."), posted by <code>StockLedger::post</code>; none if no line differs</td></tr>
                <tr><td>review</td><td><code>reject()</code> with note, <code>refreshMoved()</code> (Supervisor)</td><td><span class="g-st g-yellow">counting</span></td><td>None. Refresh sets <code>system_qty</code> to current stock and clears the line</td></tr>
                <tr><td>counting, review</td><td><code>cancel()</code> (Supervisor)</td><td><span class="g-st g-red">cancelled</span></td><td>None; lock released</td></tr>
            </tbody>
        </table>
        <ul class="g-list">
            <li><b>Approval checks</b> (one DB transaction, count row and item rows locked): every line final; a reason on every line whose |difference| exceeds <code>threshold_pct</code> of <code>system_qty</code> (compared without rounding with bcmath; <code>system_qty</code> 0 and any difference always exceeds); for every line with a difference the item's current <code>stock_qty</code> must still equal <code>system_qty</code>, otherwise the approval is refused with the list and "Refresh moved lines" is needed. Then status <code>done</code> is written FIRST (so the lock is gone), then <code>StockDocuments::recordCountAdjustment()</code> creates the ADJ document and posts it; any failure rolls everything back.</li>
            <li><b>Transaction lock:</b> a single guard, <code>StockLedger::assertNotLocked()</code>, at the top of <code>post()</code> and <code>reverse()</code>, reads <code>StockCount::lockingNow()</code> (status counting / review and <code>lock_transactions</code>). So Confirm (Receiving, Issuance, Opening), Approve (Adjustment) and Cancel of a processed document are all refused while locked; drafts and submitting an Adjustment for approval do not post and stay allowed. <code>unlock()</code> sets <code>lock_transactions</code> false and records <code>unlocked_by/at</code>.</li>
            <li><b>Audit and soft guard:</b> every save that changes a line records <code>entered_by</code> / <code>entered_at</code> (the account; <code>counted_by</code> stays the free-text counter on paper) and increments <code>rev</code>. The sheet posts the row's <code>rev</code> back; if it differs (someone else saved since the screen loaded) <code>StockCountRowChanged</code> becomes HTTP 409 <code>{reason_required, error}</code> and the DevLib sheet asks for a reason; posting again with a <code>reason</code> overwrites (Replace, never adds). A batch is one DB transaction, so a conflict saves none of it. The approve screen warns (never blocks) when the approver entered more than half of the lines (<code>StockCounts::enteredShare</code>).</li>
            <li><b>Lock / unlock:</b> <code>lock()</code> and <code>unlock()</code> (Supervisor) toggle <code>lock_transactions</code> and record <code>locked_by/at</code> and <code>unlocked_by/at</code>. Not blind: the input sheet also gets <code>system_qty</code> and a server-calculated <code>diff</code> per row.</li>
            <li><b>Schedule and reminder:</b> <code>App\Services\CountSchedule</code> keeps the schedule in the <code>constants</code> table through <code>App\Support\Constants</code> (<code>opname.frequency</code> weekly|monthly, <code>opname.weekday</code> 1..7, <code>opname.day_of_month</code> 1..31|last, <code>opname.schedule_set_at</code>); default monthly, end of month. Next date = first scheduled date after the last DONE count's <code>count_date</code> (cancelled ignored), else after <code>schedule_set_at</code>, else after today; short months clamp (Feb 28/29). <code>state()</code> feeds the Home reminder (<code>partials/count-reminder</code>, only for users with module SO; tone neutral / warn at H-3..today / danger when late) and <code>badge()</code> the menu badge (<code>Menu</code>; running or late). <code>available()</code> keeps Home and the menu alive before the migration is run. Changing the schedule is the Supervisor-only route <code>stock-counts.schedule</code>.</li>
            <li><b>Numbering:</b> <code>SO-YYMM-NNNN</code> per month; <code>doc_no</code> is unique and the creator retries on a collision, like document numbers.</li>
            <li><b>Screens:</b> the entry and review sheets are <code>x-dl::lookup-grid</code> in auto-save mode; each cell edit POSTs <code>{changed}</code> to <code>stock-counts.lines</code> / <code>stock-counts.review</code> and gets all rows back. The review screen reloads after a save (<code>resources/js/app.js</code>, attribute <code>data-reload-on-save</code>) so the server-calculated summary stays right; this needs <code>npm run build</code>.</li>
            <li><b>Rights:</b> view, fill in, print the count sheet = module <code>SO</code> (RW to write); start, review, approve, return, cancel, unlock, minutes (Berita Acara) and every value = <code>supervisor</code> middleware. Values (difference x average cost) are shown to Supervisors only.</li>
        </ul>

        <h3>Phone view (stock count on a phone)</h3>
        <ul class="g-list">
            <li><b>Device variants:</b> phone and desktop are two variants of the DEVICE, not versions. DevLib <code>Devlib\Device</code> guesses (<code>Sec-CH-UA-Mobile</code>, then <code>User-Agent</code>; phones only, tablets and "desktop site" = desktop); the cookie <code>ui</code> (<code>phone</code> | <code>desktop</code>, one year; the old value <code>hp</code> still reads as <code>phone</code>) set by the user always wins. It is set on login (<code>AuthController::login</code>, field <code>ui</code> from <code>x-dl::login-box</code> part <code>device</code>) and by <code>POST /ui</code> (<code>HpController@setDevice</code>, route <code>ui.set</code>; the Menu panel of the phone shell ("Desktop view" button; the shell has one hamburger button and no bottom tab bar) and the "Phone view" action of the desktop shell, <code>x-dl::layer-shell</code> prop <code>deviceAction</code>). Sign-in and the desktop opening screen use <code>x-dl::phone-offer auto</code>: a narrow screen (under 600 px, also when the window is dragged narrower) with no stored choice switches to the phone view by itself. <code>App\Http\Middleware\ResetViewChoice</code> (web group) forgets an old stored choice once per browser (cookie <code>ui_ver</code>, GET only; raise <code>VERSION</code> to forget again), so test-time choices do not block the automatic switch; test <code>PhoneViewTest</code>.</li>
            <li><b>Screen measuring page:</b> <code>GET /ukur</code> (route <code>ukur</code>, public, no login, <code>resources/views/ukur.blade.php</code>) is one DevLib object <code>x-dl::screen-info</code>: it measures on the device itself (CSS width x height, DPR, orientation, <code>svh</code> / <code>lvh</code> / <code>dvh</code>, <code>visualViewport</code>, touch, safe area, the screen rule "under 600 px, or phone sideways", the served view) and "Copy result" puts one text block on the clipboard (fallbacks for plain http). It is client side only: the server prints three values of the request itself (view, where the choice came from, OS) and stores nothing. Test: <code>ScreenInfoPageTest</code>.</li>
            <li><b>Screens:</b> <code>HomeController</code> returns <code>hp.home</code> when the device is <code>hp</code>; <code>x-dl::phone-shell</code> (<code>hp/layout</code>) frames every phone screen with the tabs from <code>App\Support\HpShell</code>. Modules without a phone screen open their desktop page in the frame (<code>hp.open</code>, iframe; the page's own route still enforces module access). The Transaksi tab is <code>hp.group</code>. The desktop screens are not changed.</li>
            <li><b>Count screen:</b> <code>StockCountController@phoneIndex</code> / <code>@phone</code> (routes <code>hp.counts</code>, <code>hp.count</code>, guarded as module <code>SO</code> through the <code>Modules</code> route prefix <code>hp.count</code>); view <code>hp/count</code>, script <code>resources/js/hp-count.js</code>. Same rules and endpoint as desktop: each item is saved with POST <code>stock-counts.lines</code> (one row per request, <code>compact=1</code> returns only that row plus <code>by_id</code>, <code>by</code>, <code>at</code> from <code>StockCounts::entryRows($count, true)</code>; a <code>409</code> also returns the latest row). Blind counts never send <code>system_qty</code>. Finish posts <code>hp=1</code> so the redirect goes to <code>hp.counts</code>.</li>
            <li><b>Not losing input:</b> unsent entries are kept in memory and <code>localStorage</code> (<code>hpc.stock-count.{id}</code>), retried every 8 s, on <code>online</code> and when the page is hidden; a leave-page warning appears while something is pending. A <code>409</code> (row changed by another person) asks for a reason to overwrite or discards the user's entry.</li>
        </ul>

        <h3>Rules that apply to every document</h3>
        <ul class="g-list">
            <li><b>Draft</b> never touches stock. Only a draft can be edited or deleted. Confirm needs at least one line.</li>
            <li><b>Row lock and idempotence.</b> Every state change (confirm, submit, approve, reject, cancel, delete, update) starts with <code>lockAndAssert</code>: the document row is re-read <code>FOR UPDATE</code> and its status is checked on that fresh copy. A double click or two parallel requests therefore cannot post twice, and the second one gets "Aksi ini tidak diizinkan pada status sekarang". <code>StockLedger::reverse</code> only reverses movements that have no reversal yet (<code>unreversed()</code>), so a second cancel never reverses again. Tests: <code>DoublePostingGuardTest</code>, <code>NumberingRaceTest</code>.</li>
            <li><b>Confirm</b> sets the document date/time to the moment of Confirm (Opening balance keeps the chosen date). A confirmed document is locked; correct it with an Adjustment.</li>
            <li><b>Approving an Adjustment</b> is refused if the item's stock changed since it was counted (<code>system_qty</code> differs). Reject it and count again.</li>
            <li><b>Cancel</b> needs a reason (3 to 255 characters). Before accepting a cancel or a request, <code>StockLedger::assertReversible</code> computes whether stock is still enough to pull the document back. If not, the cancel is refused and an Adjustment is needed. The result is also shown as a warning on the form before the button is pressed.</li>
            <li><b>Cancel authority:</b> a Supervisor or Administrator cancels directly. Others request. Only the requester or a Supervisor may withdraw a pending request (403 for anyone else).</li>
            <li>Detail rows use <code>x-dl::lines :row-state="true"</code> (DevLib, opt-in): each row is in state <i>edit</i> (Save + Undo) or <i>saved</i> (Edit + Delete); saved rows are locked, Undo drops a new row or restores the pre-Edit values, and the readonly (Confirmed) table shows no row buttons. Row data is posted in both states, so the form Save also commits an open, complete row.</li>
            <li>The Confirm, Delete, Cancel, Approve and Reject buttons come from the DevLib form status (<code>x-dl::form kind="document" :pipeline="true"</code>). They are not built by hand. Every stage is written to the form log.</li>
        </ul>

        <h2 id="ledger">5. Stock ledger</h2>
        <p>Only <code>App\Services\StockLedger</code> may change <code>items.stock_qty</code> and <code>items.avg_cost</code>. The columns are deliberately not mass-assignable; the ledger writes them with <code>forceFill</code>.</p>
        @include('help.diagram', ['name' => 'tg-stock-ledger', 'caption' => 'What StockLedger::post does for every document line, and how Cancel reverses it.'])
        <ol class="g-steps">
            <li><b>Lock</b> all item rows of the document at once, ordered by id (<code>lockItems</code>), so two documents holding the same items never wait on each other in a circle; each line then re-reads its item <code>FOR UPDATE</code>. Before that, <code>assertNotLocked()</code> refuses the posting while a Stok Opname holds the transaction lock.</li>
            <li><b>Signed quantity:</b> Issuance is negative; Opening and Receiving positive; Adjustment carries its own sign. A zero difference is skipped.</li>
            <li><b>Guard:</b> a resulting balance below zero throws a validation error (insufficient stock).</li>
            <li><b>Cost:</b> additions from Receiving or Opening use the line cost; positive Adjustments use the current average cost (or the item price if there is none); removals use the average cost.</li>
            <li><b>Moving average:</b> <code>(stock x avg + qty x cost) / new balance</code> on additions. Values (average cost, unit conversion, line and stock value) use exact <code>bcmath</code> arithmetic through <code>App\Support\Decimal</code> and are rounded once at the end; quantities keep their 3-decimal rounding.</li>
            <li><b>Write:</b> update the item and insert a <code>stock_movements</code> row with the balance after the movement. Products at or below the Reorder Point are returned and shown as an alert on the form. The reorder check is per <b>product</b> on the available stock (sum of batches with no expiry or expiry on/after today plus <code>sstock.reorder_exclude_days</code>), so expired batches never count as available.</li>
        </ol>
        <ul class="g-list">
            <li>Database integrity for batches (migration <code>add_integrity_constraints_to_items</code>): CHECK <code>stock_qty &gt;= 0</code> (MySQL / MariaDB only), unique <code>(product_id, expiry_date)</code> and an index on <code>expiry_date</code>. The migration stops without changing data when existing rows violate a rule. The ledger still guards negative stock itself, which is the only guard on SQLite (tests).</li>
            <li><code>stock_movements</code> rows are never edited or deleted. <code>reverse()</code> adds new rows with <code>reversal_of</code> pointing at the original; the Stock Card marks them "(CANCELLED)".</li>
            <li><code>StockValuation::series</code> builds the weekly stock-value chart from <code>balance_qty x balance_avg_cost</code> in the ledger (Supervisor only).</li>
        </ul>

        <h2 id="data">6. Data model</h2>
        @include('help.diagram', ['name' => 'tg-erd', 'caption' => 'Entity relationship diagram: key columns and relations.'])
        <table class="g-table">
            <thead><tr><th>Table</th><th>Purpose</th><th>Key relations</th></tr></thead>
            <tbody>
                <tr><td><code>categories</code>, <code>locations</code>, <code>units</code>, <code>unit_conversions</code></td><td>Item classification, storage places, units and the global conversion factors (fallback only; <code>created_by</code> / <code>updated_by</code> record who changed a pair)</td><td>items belong to a category; <code>items.location</code> stores the location <b>code</b></td></tr>
                <tr><td><code>suppliers</code> (+ <code>geo_*</code>)</td><td>Vendors with contact and region (region tables copied from tmerp; region is required only when <code>geo_city</code> has rows, see <code>GeoArea::available()</code>). <code>is_system</code> marks the built-in supplier <b>Kontan</b> (code <code>KONTAN</code>)</td><td>Receiving documents, re-order orders</td></tr>
                <tr><td><code>departments</code>, <code>recipients</code></td><td>Who receives goods; a recipient has a default department</td><td>Documents copy the <b>names</b> so they stay readable if master data changes</td></tr>
                <tr><td><code>product_units</code></td><td>Incoming units per product (<code>unit</code> = <code>units.code</code>, <code>factor</code> decimal(18,6) &gt; 0, unique <code>product_id + unit</code>, <code>created_by</code> / <code>updated_by</code> + <code>updated_at</code> = who changed it and when). The stock unit has no row</td><td>product; unit; users</td></tr>
                <tr><td><code>products</code></td><td>Item master: stable code, parameters (price, reorder point/qty, lead time), <code>tracks_expiry</code>. The single source of the parameters</td><td>category; batches (<code>items</code>); re-order records</td></tr>
                <tr><td><code>items</code></td><td>Batches: batch code, <code>expiry_date</code>, running balance <code>stock_qty</code> and <code>avg_cost</code> (cache of the ledger); name/unit/parameters are copies of the product for readers</td><td>product; lines; movements</td></tr>
                <tr><td><code>stock_documents</code></td><td>Header: number, type, status, supplier/recipient, reason, created/confirmed/approved/cancelled by and at, cancel request</td><td>creator and approver users; lines; movements</td></tr>
                <tr><td><code>stock_document_lines</code></td><td>Item, quantity, cost; Adjustment keeps <code>system_qty</code> and <code>counted_qty</code>; <code>entered_qty/unit/unit_cost</code> keep what was typed when the unit differs</td><td>document, item</td></tr>
                <tr><td><code>stock_documents.reversal_of_document_id</code></td><td>Set on the auto-created reversing ADJUSTMENT document (status approved) when a processed document is cancelled (<code>StockDocuments::recordReversal()</code>); its lines carry the ledger delta, system and resulting quantity. The ledger rows stay <code>reversal_of</code> on the original document, so the Stock Card is unchanged. The reversing document cannot be cancelled.</td><td>self</td></tr>
                <tr><td><code>stock_movements</code></td><td>The ledger: quantity, cost, balance after, user, <code>reversal_of</code></td><td>item, document, itself (reversal)</td></tr>
                <tr><td><code>stock_counts</code>, <code>stock_count_lines</code></td><td>Stok Opname: header (scope, blind, lock, threshold, cut-off, status, submitted / approved / unlocked / cancelled by and at, <code>adjustment_document_id</code>) and one snapshot line per batch (<code>system_qty</code>, <code>unit_cost</code>, <code>counted_qty</code> null = not counted, <code>recount_qty</code>, <code>counted_by</code>, <code>skipped</code>, <code>reason</code>, <code>note</code>, <code>added_manually</code>; unique <code>stock_count_id + item_id</code>)</td><td>users; items; the ADJ document created on approval</td></tr>
                <tr><td><code>constants</code></td><td>Small shared key / value settings (<code>key</code> unique, <code>value</code> text, <code>group</code>); the Stok Opname schedule uses <code>opname.*</code>; the Company screen uses <code>company.*</code> (<code>name</code>, <code>legal_name</code>, <code>address</code>, <code>phone</code>, <code>email</code>, <code>npwp</code>, <code>logo</code>, <code>kop_style</code>)</td><td>none; read through <code>App\Support\Constants</code> (per-request cache, safe when empty)</td></tr>
                <tr><td><code>reorder_orders</code></td><td>Simple order record per product (<code>supplier_id</code>, <code>qty</code> in the stock unit, <code>ordered_at</code>, <code>ref_no</code>, <code>note</code>; old rows may have no supplier or qty). <code>received_qty</code> and <code>first_received_at</code> are filled by <code>ReorderOrders::reconcile</code>. Closed when confirmed Receiving quantities reach <code>qty</code> (<code>close_reason = 'received'</code>, <code>receiving_id</code> = the completing document), or manually (<code>'manual'</code>)</td><td>product, supplier, receiving document</td></tr>
                <tr><td><code>sessions</code></td><td>Database sessions (<code>SESSION_DRIVER=database</code>). Deactivating a user deletes the user's rows here, which logs that account out at once</td><td>users</td></tr>
                <tr><td><code>users</code>, <code>user_groups</code> (<code>is_active</code>: groups are deactivated, never deleted), <code>user_group_modules</code></td><td>Accounts, groups, and per-module access (none / RO / RW)</td><td>user belongs to a group</td></tr>
            </tbody>
        </table>

        <h3>Migrations</h3>
        <p>Migrations are the source of truth for the schema (<code>database/sql/sstock_mysql_ddl.sql</code> is out of date). In order:</p>
        <table class="g-table">
            <thead><tr><th>Migration</th><th>What it does</th></tr></thead>
            <tbody>
                <tr><td><code>0001_01_01_00000x</code></td><td>Laravel base: <code>users</code>, password reset tokens, sessions, cache, jobs</td></tr>
                <tr><td><code>create_stock_tables</code></td><td><code>categories</code>, <code>items</code>, <code>suppliers</code>, <code>stock_documents</code>, <code>stock_document_lines</code>, <code>stock_movements</code></td></tr>
                <tr><td><code>create_geo_tables_and_supplier_contact</code></td><td><code>geo_country, geo_region, geo_city, geo_district, geo_village</code> (filled by <code>sstock:import-geo</code>) and supplier contact and region columns</td></tr>
                <tr><td><code>create_units_table</code>, <code>create_unit_conversions_table</code>, <code>add_kind_to_units</code>, <code>localize_unit_names</code></td><td>Unit master, conversion factors, colour kind, Indonesian unit names</td></tr>
                <tr><td><code>add_entered_unit_to_stock_document_lines</code></td><td><code>entered_qty / entered_unit / entered_unit_cost</code>: what was typed when the unit differs from the stock unit</td></tr>
                <tr><td><code>create_reorder_orders_table</code></td><td>Re-order log</td></tr>
                <tr><td><code>create_departments_and_recipients</code></td><td><code>departments</code>, <code>recipients</code> and the id and name columns on <code>stock_documents</code></td></tr>
                <tr><td><code>create_user_groups</code>, <code>add_access_to_user_group_modules</code></td><td>Groups and per-module access (none / RO / RW)</td></tr>
                <tr><td><code>add_confirm_and_cancel_to_stock_documents</code>, <code>add_cancel_request_to_stock_documents</code></td><td><code>confirmed_by/at</code>, <code>cancelled_by/at</code>, <code>cancel_reason</code>, <code>stock_movements.reversal_of</code>, <code>cancel_requested_by/at</code></td></tr>
                <tr><td><code>add_expiry_locations_and_category_active</code></td><td><code>locations</code> master, item expiry date, <code>categories.is_active</code></td></tr>
                <tr><td><code>create_products_and_batches</code></td><td>Two-level item model: <code>products</code>, <code>items.product_id</code> (batches), <code>reorder_orders.product_id</code>; old items are merged into products</td></tr>
                <tr><td><code>add_is_active_to_user_groups</code></td><td>Activate / Deactivate for groups</td></tr>
                <tr><td><code>add_reversal_of_document_to_stock_documents</code></td><td><code>stock_documents.reversal_of_document_id</code>: the automatic reversing ADJUSTMENT points at the cancelled document</td></tr>
                <tr><td><code>add_audit_to_unit_conversions</code></td><td><code>created_by</code> / <code>updated_by</code> on the global conversions</td></tr>
                <tr><td><code>add_integrity_constraints_to_items</code></td><td>CHECK <code>stock_qty &gt;= 0</code> (MySQL / MariaDB), unique <code>(product_id, expiry_date)</code>, index on <code>expiry_date</code>; stops without changing data when existing rows violate them</td></tr>
                <tr><td><code>give_expiry_report_to_groups</code>, <code>give_sample_data_to_administrator_groups</code></td><td>Data migrations: grant the new modules to existing administrator groups</td></tr>
                <tr><td><code>add_system_supplier_kontan</code></td><td><code>suppliers.is_system</code> and the system supplier row <code>KONTAN</code> (idempotent)</td></tr>
                <tr><td><code>add_supplier_qty_note_to_reorder_orders</code></td><td><code>reorder_orders.supplier_id</code> (FK, null on delete), <code>qty</code> decimal(15,3) null, <code>note</code> null: simple order record (nullable columns only; old rows unchanged)</td></tr>
                <tr><td><code>add_receipt_progress_to_reorder_orders</code></td><td><code>reorder_orders.received_qty</code> decimal(15,3) default 0, <code>first_received_at</code> null, <code>close_reason</code> null ('received' | 'manual'); closed rows are backfilled (columns only, nothing deleted)</td></tr>
                <tr><td><code>create_product_units_table</code></td><td><code>product_units</code>: incoming units per product with factor to the stock unit (adds a table only; no existing data is changed; CHECK factor &gt; 0 on MySQL / MariaDB)</td></tr>
                <tr><td><code>give_order_history_to_groups</code></td><td>Data migration only: groups with RCV rights get module <code>ORD</code> with the same level (idempotent)</td></tr>
                <tr><td><code>give_company_to_groups</code></td><td>Data migration only: grants module <code>CMP</code> (RW) to the groups that already write ADJ, OPN, UOM, CAT or LOC (idempotent)</td></tr>
                <tr><td><code>create_stock_counts_tables</code>, <code>give_stock_count_to_groups</code></td><td>Stok Opname tables (new tables only; no existing data changed); data migration that grants module <code>SO</code> (RW) to every group that has RW on ADJ or RCV (idempotent)</td></tr>
                <tr><td><code>add_lock_audit_to_stock_counts</code>, <code>add_entry_audit_to_stock_count_lines</code>, <code>create_constants_table</code></td><td>New columns / table only: <code>locked_by/at</code>; <code>entered_by</code>, <code>entered_at</code>, <code>rev</code> on lines; <code>constants</code> (skipped if it already exists)</td></tr>
            </tbody>
        </table>

        <h2 id="access">7. Access and security</h2>
        @include('help.diagram', ['name' => 'tg-access-flow', 'caption' => 'Login and per-request authorization.'])
        <ol class="g-steps">
            <li>A request must be authenticated (<code>auth</code>), otherwise it goes to Login.</li>
            <li><code>EnsureActive</code> logs out a user who was deactivated during a live session (redirect to Login, or 403 JSON); deactivating also clears <code>remember_token</code> and the user's rows in <code>sessions</code>. The message is "Akun Anda dinonaktifkan.". The last active Administrator cannot be deactivated, and nobody can deactivate or change the role of their own account (<code>UserController</code>).</li>
            <li><code>EnsureModuleAccess</code> maps the route to a module code with <code>Modules::forRoute</code>. Access needs <b>both</b> a sufficient role and the module granted by the user's group.</li>
            <li>A module with RO access refuses every non-GET request.</li>
            <li>The controller or form request then checks per-document-type rights.</li>
        </ol>
        <ul class="g-list">
            <li>Roles: <code>data_entry</code> &lt; <code>supervisor</code> &lt; <code>administrator</code>. Administrators always have full access. A user without a group gets the role default (<code>Modules::defaults</code>).</li>
            <li><code>App\Support\Modules</code> is the single module list; it feeds the menu, the group settings, the module list screen and the route guard. A new module is one new row there.</li>
            <li>Extra middleware: <code>supervisor</code> (edit items, units, categories, locations, adjustment report) and <code>administrator</code> (users, groups, modules, this guide).</li>
            <li>Print: <code>docs.print</code> (<code>StockDocumentPrint</code> on DevLib <code>x-dl::print-doc</code>) is available in every status and prints the MODE (stamp + watermark for non-valid modes). Approve and Reject are Supervisor only. Password reset uses an e-mail link (throttled to 5 per minute, valid 60 minutes, single use).</li>
            <li>Incoming-unit factors (<code>ProductUnits::save</code>, used by the product form route <code>products.units.save</code> and the Receiving dialog <code>docs.conversion</code>): anyone with write access may create a factor and may correct one that no confirmed Receiving has used yet (a cancelled one still counts as used; drafts do not). A factor already used by a confirmed document changes only for the holder of write access to <code>UOM</code> (Supervisor); everyone else gets 403 "Faktor ini sudah dipakai di dokumen; hubungi Supervisor untuk mengubahnya.". Old documents never change: lines keep the converted <code>qty</code> and the typed <code>entered_*</code>; a locked document shows its stored quantity. The stock unit of a product cannot be changed while it has incoming units. The global <code>unit_conversions</code> remain a fallback only; the Unit master (UOM, Supervisor) still edits them.</li>
            <li>Login: username or e-mail plus password, <code>is_active</code> required, at most 5 failed attempts per login + IP (<code>RateLimiter</code>, then a wait message). Session: there is no "remember me" (the checkbox is removed and the input is ignored; no <code>remember_token</code> or recaller cookie). <code>.env.example</code> sets <code>SESSION_LIFETIME=30</code> minutes and <code>SESSION_DRIVER=database</code> (<code>config/session.php</code> falls back to 120 minutes when the variable is missing). <code>x-dl::idle-logout</code> is mounted once in <code>layout.blade.php</code> for logged-in pages only, with <code>minutes</code> = <code>config('session.lifetime')</code>: a warning with a countdown and a <b>Stay signed in</b> button shows 2 minutes before the end, then it POSTs <code>logout</code> and goes to Login. Activity pings <code>GET session/ping</code> (route <code>session.ping</code>, 204, behind <code>auth</code> and <code>active</code>, no module code), which extends the server session.</li>
            <li>Quick add: dropdowns of master data carry a plus button (DevLib <code>x-dl::field.select</code> prop <code>add</code>) built by <code>App\Support\QuickAdd</code>, which returns nothing unless the user can <b>write</b> the master's module (<code>User::canWrite</code>: role and group RW, the same rule as the route guard). It posts JSON to the master's normal <code>store</code> route (Supplier, Department, Recipient: any writer; Category, Location, Unit: Supervisor) and expects <code>201 {id, name}</code>, or 422 with Laravel errors. Supplier codes are generated (<code>Supplier::nextCode</code>, <code>SUP-001</code>...) and region is not asked; names must be unique, except Recipient. Locked documents and the report filters have no plus button.</li>
            <li>System supplier: <code>Kontan</code> (<code>suppliers.is_system</code>, created by migration <code>add_system_supplier_kontan</code> and <code>SupplierSeeder</code>) cannot be deactivated, renamed or deleted (<code>SupplierRequest</code>, <code>SupplierController::toggle</code>, model <code>deleting</code> guard) and sorts first in every supplier dropdown (<code>Supplier::forSelect</code>).</li>
            <li>Messages worth knowing: <code>EnsureModuleAccess</code> answers 403 "Anda tidak punya akses ke modul ini." (no access) or "Akses Anda ke modul ini hanya-baca (RO)." (non-GET on an RO module).</li>
            <li>Known gaps: no segregation of duties (the creator of an Adjustment may approve it; the Stok Opname approval screen only warns when the approver entered more than half of the lines); the Save button still shows on RO modules (the server rejects it).</li>
        </ul>

        <h3>Module list (<code>App\Support\Modules</code>)</h3>
        <table class="g-table">
            <thead><tr><th>Code</th><th>Menu</th><th>Group</th><th>Minimum role</th><th>Guarded route prefix</th></tr></thead>
            <tbody>
                <tr><td>ITM</td><td>Barang</td><td>Data Master</td><td>Data Entry</td><td><code>products.</code> (add: any role; edit and toggle: Supervisor)</td></tr>
                <tr><td>SUP, DEP, RCP</td><td>Suppliers, Departemen, Penerima</td><td>Data Master</td><td>Data Entry</td><td><code>suppliers.</code>, <code>departments.</code>, <code>recipients.</code></td></tr>
                <tr><td>UOM, CAT, LOC</td><td>Satuan, Kategori, Lokasi Penyimpanan</td><td>Data Master</td><td>Supervisor</td><td><code>units.</code>, <code>categories.</code>, <code>locations.</code></td></tr>
                <tr><td>CMP</td><td>Perusahaan</td><td>Data Master</td><td>Supervisor</td><td><code>company.edit</code>, <code>company.update</code> (the route <code>company.logo</code> is public and has no module)</td></tr>
                <tr><td>OPN, ADJ</td><td>Saldo Awal, Penyesuaian Stok</td><td>Transaksi</td><td>Supervisor</td><td><code>docs.*</code> with <code>{type}</code> = opening / adjustment</td></tr>
                <tr><td>ORD</td><td>Riwayat Pemesanan</td><td>Transaksi</td><td>Data Entry</td><td><code>reorder-orders.</code> (all of them: GET list and detail are readable with RO; the POST routes <code>store</code> = Catat Pemesanan and <code>close</code> = Tutup Pesanan need RW)</td></tr>
                <tr><td>SO</td><td>Stok Opname</td><td>Transaksi</td><td>Data Entry</td><td><code>stock-counts.</code> (start, review, approve, return, cancel, unlock, minutes: Supervisor middleware on the route)</td></tr>
                <tr><td>RCV, ISS</td><td>Penerimaan Barang, Pengeluaran Barang</td><td>Transaksi</td><td>Data Entry</td><td><code>docs.*</code> with <code>{type}</code> = receiving / issuance</td></tr>
                <tr><td>R-CARD, R-SUM, R-IN, R-OUT, R-EXP</td><td>Kartu Stok, Ringkasan Stok, Laporan Penerimaan / Pengeluaran / Kedaluwarsa</td><td>Laporan</td><td>Data Entry</td><td><code>reports.*</code></td></tr>
                <tr><td>R-ADJ</td><td>Laporan Opname &amp; Penyesuaian</td><td>Laporan</td><td>Supervisor</td><td><code>reports.adjustment</code></td></tr>
                <tr><td>USR, GRP, SMP, MOD</td><td>Pengguna, Grup, Data Contoh, Daftar Modul</td><td>Keamanan</td><td>Administrator</td><td><code>users.</code>, <code>groups.</code>, <code>sample.</code>, <code>modules.</code></td></tr>
            </tbody>
        </table>
        <p class="g-note">Routes that belong to no module (home, logout, session ping, Help) are open to every logged-in user. The User's Guide lists only the modules the reader may open (<code>User::canAccess</code>); the Technical Guide route sits in the <code>administrator</code> middleware group and its tab link is rendered only for Administrators.</p>

        <h2 id="screens">8. Dashboard and reports</h2>
        <ul class="g-list">
            <li><b>Home screen</b> (<code>HomeController</code>): clickable KPIs (<code>data-open-tab</code> opens the module as a shell tab), the Stok Opname reminder (<code>partials/count-reminder</code>, only for users with module <code>SO</code>), Reorder Point alert with order status, near-expiry alert (batches with stock and <code>expiry_date</code> within <code>Item::EXPIRY_WARN_DAYS</code> = 30 days), and, for Supervisors, the approval queue (<code>approval</code> and <code>cancel_pending</code> documents) and the stock-value chart.</li>
            <li><b>Re-order log:</b> "Record order" creates a <code>reorder_orders</code> row; confirmed Receiving documents add to <code>received_qty</code> and close it once <code>qty</code> is reached. Open orders carry an information note (<code>ReorderOrder::note()</code>: order date, reference, who recorded it, and "received since ordering" = incoming movements of confirmed, not cancelled Receiving documents for all batches of the product since <code>ordered_at</code>) on the Home alert, the Stock Summary report (column <i>Info Pemesanan</i>) and the product form. Lead time (<code>App\Services\LeadTimes</code> on the DevLib engine <code>Devlib\Engine\LeadTime</code>): the orders of a product become records <code>{start: ordered_at, end: closed_at, key: id}</code> (<code>forMany()</code> does one query for many products; open orders have no end). <code>end</code> is now <code>first_received_at</code> (the FIRST receiving allocated to the order, also while it is only partially received); orders without one, including orders closed manually without any receiving, are not counted, and a cancelled Receiving is never counted because <code>reconcile</code> clears it. Options: last 5 orders, minimum 3, fallback = <code>products.lead_time_days</code> (0 = unset), late tolerance 1 day. Orders that have received nothing yet get the engine sentences (<code>describeExpected</code>, and <code>describeStatus</code> when late) appended to the note; once the first receiving has arrived they are no longer flagged late; with no figure nothing is added. The product form shows read-only hints (automatic lead time when the source is data; a suggestion box <i>Saran pesan ulang</i> from <code>App\Services\ReorderAdvice::advise</code>: with u = 90-day average daily usage of confirmed, not cancelled Issuance movements, reorder point = ceil(u x (lead time + safety days)) and reorder qty = ceil(u x cover days), at least 1; defaults 7 safety days and 30 cover days (<code>ReorderAdvice::SAFETY_DAYS</code> / <code>COVER_DAYS</code>); the two day values can be tried on screen (client-side recalculation, not stored) and the <i>Pakai saran</i> button, Supervisor only, only fills the two fields, saved by the normal Save; the box needs an effective lead time and u &gt; 0, otherwise a one-line note) and the Stock Summary has a column <i>Lead Time Otomatis</i>; the manual lead time and the reorder point are never written. The Record Order popup (<code>ReorderOrderController</code>) stores <code>supplier_id</code> (active supplier, FK null on delete), <code>qty</code> (&gt; 0, in the stock unit; nullable because old rows have none), <code>ordered_at</code>, optional <code>ref_no</code> (stored as an empty string when blank) and <code>note</code>; the note text and the report columns skip the supplier / quantity parts for old rows. This is a simple record, not a PO. The alert calculation is unchanged. <b>Closing rule</b> (<code>App\Services\ReorderOrders::reconcile</code>, called by <code>StockDocuments</code> on confirm and on every cancel of a Receiving): per product it takes the confirmed, not cancelled Receiving movements (stock unit, by time) and the product's orders by <code>ordered_at, id</code> and allocates FIFO, only receipts on or after the order date; each order with <code>qty</code> takes up to its <code>qty</code> and gets <code>received_qty</code>, <code>first_received_at</code> and, when complete, <code>closed_at</code> / <code>close_reason = 'received'</code> / <code>receiving_id</code> (the completing document), else all three are cleared; excess flows to the next order. Orders without <code>qty</code> (old rows) are closed by the first receiving on or after the order date, as before. Orders with <code>close_reason = 'manual'</code> (button <i>Tutup Pesanan</i> on the Home alert, <code>reorder-orders.close</code>, open orders only, optional reason appended to <code>note</code>) are never allocated or reopened. The result depends only on the receiving data, so reconcile is idempotent. Orders closed before this rule were backfilled by the migration (<code>first_received_at = closed_at</code>, <code>close_reason = 'received'</code>, <code>received_qty = qty</code>) but are recomputed like any other on the next reconcile of their product.</li>
            <li><b>Order history</b> (module <code>ORD</code>, <code>ReorderOrderController@index</code> / <code>show</code>, views <code>reorder-orders/*</code>): a plain grid of every order with every status (<code>ReorderOrder::statusLabel()</code>: Dipesan, Diterima sebagian, Selesai, Ditutup manual), a GET date-range filter on <code>ordered_at</code>, status boxes and a supplier filter in the grid, no money. Legacy orders without <code>qty</code> show "-". The detail page lists the receipts allocated to the order through <code>ReorderOrders::allocations()</code>, a read-only copy of the FIFO of <code>reconcile</code> (<code>ReorderOrderHistoryTest</code> checks that its sum equals <code>received_qty</code>; manual orders are not itemised). The Close Order button posts to the existing <code>reorder-orders.close</code> with <code>return=history</code> and is shown only for open orders and users who can write <code>ORD</code>. Migration <code>give_order_history_to_groups</code> copies the group right on <code>RCV</code> (RW to RW, RO to RO).</li>
            <li><b>Reports</b> (<code>ReportController</code>). Period filters (<code>from</code>, <code>to</code>) default to the first of the current month until today (<code>period()</code>). Stock Card and Stock Summary are per batch (<code>items</code>), not per product; the Stock Card needs an item and reads <code>stock_movements</code>. The opening balance of a report is the balance after the last movement before <code>from</code>, so an <code>OPENING</code> movement dated before the period is folded into it and one dated inside the period shows as an <code>AWAL</code> row. Stock Summary: <i>in</i> = Receiving + Opening movements; the reorder status is per product from the closing balance of non-expired batches. Receiving and Issuance list lines of <code>confirmed</code> and <code>cancel_pending</code> documents only (draft and cancelled are excluded); the Opname &amp; Adjustment report (Supervisor) lists Adjustment documents of every status, with a status filter, including the automatic reversal and Stok Opname adjustments. Expiry has no period (<code>days</code>, default 30, and category). Prices and values need <code>isSupervisor()</code>.</li>
        </ul>

        @include('help.diagram', ['name' => 'tg-reorder-flow', 'caption' => 'Re-order flow: alert, Record Order, and closing by Receiving.'])

        <h3>Greeting, weather and network line</h3>
        <ul>
            <li><b>Greeting:</b> <code>partials/hello</code> (included by <code>home</code> and <code>hp.home</code>): <code>x-dl::greeting</code> (first name, no emoji, follows the language), <code>x-dl::clock mode=&quot;line&quot;</code> (long date and time without seconds; short date on the phone view) and <code>x-dl::weather</code> (<code>variant=&quot;card&quot;</code> on desktop, <code>chip</code> on the phone view).</li>
            <li><b>Weather follows the device:</b> <code>x-dl::weather</code> uses <code>navigator.geolocation</code> by default (HTTPS or localhost only; <code>maximumAge</code> 6 hours, timeout 8 seconds, no high accuracy), the city name comes from BigDataCloud <code>reverse-geocode-client</code>, the weather from Open-Meteo, both called by the browser with coordinates rounded to 2 decimals; the answer is cached in <code>localStorage</code> (<code>dl-wx</code>) for 1 hour and a refusal is remembered for 7 days (<code>dl-wx-no</code>). Fallback chain: cache, device location, the company fallback place (<code>Company::weather()</code>), nothing (card hidden). On plain <code>http://</code> (LAN IP, <code>.test</code>, an Ubuntu server without a certificate) the device location never works, so the Company fallback is used until HTTPS is installed. The only outgoing calls of the app are these two services, made by the browser, never by the server.</li>
            <li><b>Fallback place:</b> the Company screen has Kota / Lintang / Bujur (<code>company.weather_city</code>, <code>weather_lat</code>, <code>weather_lon</code> in <code>constants</code>); <code>CompanyRequest</code> validates latitude -90..90 and longitude -180..180 (numeric, optional, both or none, a decimal comma is accepted); <code>Company::weather()</code> returns <code>['city','lat','lon']</code> or <code>null</code>.</li>
            <li><b>Network line:</b> <code>AppServiceProvider::boot</code> binds <code>devlib.net</code> to <code>Devlib\NetworkInfo::of(request())</code>; <code>x-dl::layer-menu</code> (desktop user row) and <code>x-dl::phone-shell</code> (Menu list) then draw <code>x-dl::net-line</code> instead of the old host text. The browser pings the DevLib route <code>GET /devlib/ping</code> (204, no session, no cookie, throttled) every 10 seconds while the tab is visible; two failures in a row = &quot;Server tidak terjangkau&quot; (server IP blinks), the browser <code>offline</code> event = &quot;Perangkat offline&quot; (client IP blinks). <code>session.ping</code> is not used on purpose: it extends the 30 minute session and a login redirect would look like a failure. A browser cannot tell a broken LAN from a broken internet; the line only says whether the server answered.</li>
            <li><b>Version label:</b> <code>AppServiceProvider::boot</code> binds <code>devlib.version</code> to <code>Devlib\AppVersion::of(base_path())</code>: <code>ver 0.&lt;git commit count&gt;</code> plus one stage letter from <code>APP_STAGE</code> (<code>config('devlib.stage')</code>, default <code>alpha</code>; <code>A</code> alpha, <code>B</code> beta, <code>RC</code>; empty = release = latest tag such as <code>ver 1.0.0</code>, no letter). The count is cached under <code>storage/framework/cache</code> keyed by the mtime of <code>.git/logs/HEAD</code>, so git runs once per new commit; without git (a server that only receives files) the file <code>VERSION</code> (<code>0.NNN</code>) is used, then <code>0.0</code>. It is drawn by <code>x-dl::env-badge part=&quot;version&quot;</code> in the logo title of the left menu and under the login form (<code>login</code>, <code>login-hp</code>). The server user running PHP must be allowed to read the repository (git <code>safe.directory</code>), otherwise the fallback is used.</li>
            <li><b>Tests:</b> <code>HomeGreetingTest</code> (greeting, weather props, fallback, phone chip, network binding, ping route) and <code>CompanyTest</code> (fallback place fields).</li>
        </ul>

        <h3>Company header of every printout</h3>
        <p>One screen (module <code>CMP</code>, Data Master &gt; Perusahaan, Supervisor only; <code>CompanyController</code>, <code>CompanyRequest</code>, view <code>company/form</code>) feeds one class, <code>App\Support\Company</code>, which is the only source of the print header. The application keeps the name STOKU (login, menu, titles); only printouts use the company name.</p>
        <ul class="g-list">
            <li><b>Where the data lives:</b> <code>constants</code> keys <code>company.name</code>, <code>legal_name</code>, <code>address</code>, <code>phone</code>, <code>email</code>, <code>npwp</code>, <code>logo</code> (the file name, always <code>logo.png</code>) and <code>kop_style</code> (<code>logo_name_address</code> | <code>logo_name</code> | <code>name</code>). Nothing new in the schema. Defaults before the first save: name = <code>config('devlib.company.name')</code>, style = <code>logo_name</code> when a logo exists, else <code>name</code>.</li>
            <li><b>Wiring:</b> <code>AppServiceProvider::boot</code> registers <code>Document::companyUsing(fn () =&gt; Company::kop())</code>, so <code>x-dl::print-doc</code> (stock documents) and <code>x-dl::print-sheet</code> (count sheet, minutes) use it through <code>Kop::of()</code> of DevLib. The six reports pass <code>Company::report()</code> to <code>x-dl::report</code> (it reads <code>address</code> and <code>contact</code> instead of <code>lines</code>). Nothing builds its own header. <code>kop()</code> gives at most 3 lines (address lines, then <code>Telp. ... · e-mail</code>); the tax id prints only on stock documents.</li>
            <li><b>Style rules</b> (in DevLib): a logo style without a logo falls back to the name-only look but the stored choice stays; a legal name equal to the name (ignoring case and spaces) is not printed twice. <code>report()</code> keeps the old tagline only while the company has never been saved.</li>
            <li><b>Logo upload:</b> PNG, JPEG or WebP only, checked by content (<code>mimetypes</code> and <code>getimagesizefromstring</code>; SVG, GIF and fake extensions are refused), max 1 MB, 32 to 4000 px per side. GD resizes it to at most 600 px wide and stores a standard PNG in <code>storage/app/company/logo.png</code>; the user never chooses a file name. <code>?v=</code> in the URL is the file time, so browsers refresh.</li>
            <li><b>Serving:</b> route <code>company.logo</code> sits outside <code>auth</code> because the public link of <code>x-dl::doc-share</code> prints the same header. It answers with <code>Content-Type: image/png</code>, <code>Cache-Control: public, max-age=86400</code> and <code>X-Content-Type-Options: nosniff</code> through <code>response()-&gt;file()</code>, so <code>storage:link</code> is not needed. URLs are relative (<code>route(..., absolute: false)</code>).</li>
            <li><b>Access:</b> migration <code>give_company_to_groups</code> grants <code>CMP</code> (RW) to groups that already write ADJ, OPN, UOM, CAT or LOC; users without a group get it from the role default. Test: <code>CompanyTest</code>.</li>
        </ul>

        <h2 id="conventions">9. Development conventions</h2>
        <table class="g-table">
            <thead><tr><th>Topic</th><th>Rule</th></tr></thead>
            <tbody>
                <tr><td>Language</td><td>Everything shown to users is Indonesian. Add new labels to <code>Indonesian</code>; validation messages live in <code>lang/id/validation.php</code>; currency stays <code>IDR</code></td></tr>
                <tr><td>Grids</td><td>Transaction grids are plain (bold document number, one colored Status badge). Master grids use a colored box per row (<code>box</code>, <code>hue</code> from <code>Palette</code>)</td></tr>
                <tr><td>Forms</td><td>The Back button always goes to the module grid (<code>:back</code>); a form must not submit twice</td></tr>
                <tr><td>Seeders</td><td>Sample data lives only in <code>database/seeders</code>; sample transactions go through <code>StockDocuments</code> then <code>StockLedger</code>; a schema change updates the seeders in the same change</td></tr>
                <tr><td>DevLib</td><td>Changes must be opt-in because tmerp uses the old behaviour; fix root causes in DevLib, not in the app</td></tr>
                <tr><td>Git</td><td>Commit and push every change; never force-push; never commit <code>.env</code></td></tr>
                <tr><td>Help</td><td>User's Guide (Indonesian) is split per section in <code>resources/views/help/ug/*.blade.php</code> and wired by <code>help/user-guide.blade.php</code>; each module has an anchor (<code>m-</code>, <code>t-</code>, <code>r-</code>, <code>sec-</code>) and every section follows the same pattern (purpose, who, steps, notes, common problems). Diagrams are inline SVG in <code>resources/help/diagrams</code>, included with <code>help.diagram</code>. <code>HelpTest</code> compares the module list (<code>Modules::all()</code>) with those anchors, so a new module needs a help section. Change the UG and this guide together with the behaviour</td></tr>
            </tbody>
        </table>
        <h3>Adding a new document type</h3>
        <ol class="g-steps">
            <li>Add a case to <code>StockDocumentType</code> (slug, prefix, label, quantity sign).</li>
            <li>Register it in the <code>whereIn</code> of the <code>docs/{type}</code> route and in <code>Modules</code> (<code>all</code> and <code>forRoute</code>).</li>
            <li>Add rules to <code>StockDocumentRequest</code> and <code>StockDocuments</code>.</li>
            <li>Update the dictionary and the seeders, and add lifecycle tests in <code>DocumentLifecycleTest</code>.</li>
        </ol>

        <h2 id="operations">10. Operations</h2>
        <pre class="g-code">composer setup     # install, .env, key, create DB and MySQL user, migrate, seed, build
composer sample    # migrate:fresh --seed --force (ERASES the database including geo_*); then run sstock:import-geo
php artisan test   # in-memory SQLite
vendor/bin/pint --dirty
npm run build      # required after editing resources/css/app.css</pre>
        <ul class="g-list">
            <li>Test accounts (development only; change before production): <code>admin</code>, <code>supervisor</code>, <code>entry</code> (see <code>UserSeeder</code>).</li>
            <li>Tests: see <a href="#testing">Testing</a> below.</li>
            <li>Production: set SMTP (<code>MAIL_*</code>, currently <code>log</code>) and replace the test passwords. <code>database/sql/sstock_mysql_ddl.sql</code> is out of date; migrations are the source of truth.</li>
        </ul>

        <h3 id="install">Install steps</h3>
        <p>The README (Indonesian, written for new testers) has the full walk-through. Short version:</p>
        <ol class="g-steps">
            <li>Install PHP 8.3 or newer (extension <code>pdo_mysql</code>), Composer 2, MySQL 8, Node 20.19+ or 22.12+, and Git.</li>
            <li>Clone <code>devlib</code> and <code>sstock</code> side by side: Composer takes <code>jo/devlib</code> from the path repository <code>../devlib</code>. Both repositories are private.</li>
            <li><code>composer install</code>, copy <code>.env.example</code> to <code>.env</code>, then <code>php artisan key:generate</code>.</li>
            <li>Set <code>DB_*</code> and the MySQL admin account (<code>DB_ADMIN_USERNAME</code>, <code>DB_ADMIN_PASSWORD</code>, used only by the installer). On a test machine also set <code>APP_ENV=local</code>, <code>APP_DEBUG=true</code> and <code>SSTOCK_SAMPLE_REFRESH=true</code>.</li>
            <li><code>php artisan sstock:install</code> creates the database and the MySQL user, migrates, tries to copy the region tables (a failure is not fatal), and seeds once when there are no users. It is safe to run again.</li>
            <li><code>npm install --ignore-scripts</code>, then <code>npm run build</code> (<code>public/build</code> is not in Git).</li>
            <li>Serve with Herd (<code>sstock.test</code>) or <code>php artisan serve</code>. <code>composer setup</code> runs steps 3 (key and <code>.env</code> copy), 5 and 6 without prompts.</li>
        </ol>

        <h3>Artisan commands</h3>
        <table class="g-table">
            <thead><tr><th>Command</th><th>Purpose</th></tr></thead>
            <tbody>
                <tr><td><code>php artisan sstock:install</code></td><td>First deploy: creates the MySQL database and the <code>sstock_usr</code> user (needs a MySQL admin: <code>--admin-user / --admin-password</code>, or <code>DB_ADMIN_USERNAME / DB_ADMIN_PASSWORD</code>, or it asks), migrates, copies region data, seeds once. Safe to re-run (never overwrites data)</td></tr>
                <tr><td><code>php artisan sstock:import-geo</code></td><td>Copies <code>geo_*</code> from the tmerp database (<code>--source</code>, default env <code>GEO_SOURCE_DB</code> or <code>tm2mst</code>). Skipped if the tables are filled, unless <code>--fresh</code>. Needs a MySQL admin account</td></tr>
                <tr><td><code>php artisan sstock:wallpapers</code></td><td>Converts new images dropped into <code>public/media/bg/sstock</code> to JPG (max width 2560, odd ratios cropped to 16:9, originals moved to <code>storage/app/wallpaper-source</code>). Also runs by itself after a login page response (<code>defer()</code>)</td></tr>
                <tr><td><code>php artisan migrate --force</code></td><td>Apply migrations on a server</td></tr>
                <tr><td><code>composer dev</code></td><td>Local development runner (<code>php artisan dev</code>)</td></tr>
            </tbody>
        </table>

        <h3>Seeders and sample data</h3>
        <ul class="g-list">
            <li><code>DatabaseSeeder</code> runs, in order: <code>UserSeeder</code> (accounts admin, supervisor, entry), <code>CategorySeeder</code>, <code>SupplierSeeder</code>, <code>ItemSeeder</code> (products, batches, locations; units come from the migration), <code>OrganizationSeeder</code> (departments, recipients), <code>SampleTransactionSeeder</code>.</li>
            <li>Master seeders are idempotent (<code>updateOrCreate</code> keyed by a natural code). <code>SampleTransactionSeeder</code> posts through <code>StockDocuments</code> then <code>StockLedger</code>, never by direct inserts, and is skipped when documents already exist. It covers every important status (draft, confirmed, adjustment waiting and approved, items below the Reorder Point).</li>
            <li><b>Sample Data screen</b> (Keamanan &gt; Data Contoh, <code>SampleDataController</code>, <code>App\Services\SampleData</code>): the Administrator types <code>SEGARKAN</code>; the database is dropped and rebuilt from migrations plus <code>DatabaseSeeder</code>; <code>geo_*</code> is preserved; the user is logged out. The last refresh is logged in <code>storage/app/sample-refresh.json</code>. Disabled in production unless <code>SSTOCK_SAMPLE_REFRESH=true</code>.</li>
            <li><code>composer sample</code> is the CLI twin (<code>migrate:fresh --seed --force</code>) but it also erases <code>geo_*</code>; run <code>sstock:import-geo</code> afterwards.</li>
            <li>A schema change must update the seeders in the same change; <code>SeedersTest</code> runs the seeders twice and checks that stock equals the ledger.</li>
        </ul>

        <h3>Configuration</h3>
        <table class="g-table">
            <thead><tr><th>Key</th><th>Meaning</th></tr></thead>
            <tbody>
                <tr><td><code>APP_ENV</code>, <code>APP_DEBUG</code></td><td><code>.env.example</code> ships <code>production</code> and <code>false</code>. A test machine sets <code>local</code> and <code>true</code> in its own <code>.env</code>; never in production</td></tr>
                <tr><td><code>SESSION_LIFETIME=30</code></td><td>Server session minutes (<code>config/session.php</code> falls back to 120 if the variable is missing); also the idle-logout time in the browser (the warning shows 2 minutes before)</td></tr>
                <tr><td><code>SESSION_DRIVER=database</code></td><td>Sessions live in the <code>sessions</code> table, so deactivating a user can delete the user's sessions</td></tr>
                <tr><td><code>APP_LOCALE=id</code></td><td>Indonesian screens (dictionary active only for <code>id</code>)</td></tr>
                <tr><td><code>DB_*</code>, <code>DB_ADMIN_*</code></td><td>Application MySQL account; admin account used only by the installer and <code>sstock:import-geo</code></td></tr>
                <tr><td><code>MAIL_*</code></td><td><code>MAIL_MAILER=log</code> in development (reset links only appear in <code>storage/logs/laravel.log</code>); production needs SMTP</td></tr>
                <tr><td><code>SSTOCK_SAMPLE_REFRESH</code></td><td>Allow the Sample Data screen. <code>.env.example</code> sets <code>false</code>; the code default (<code>config/sstock.php</code>) is on outside production. <code>phpunit.xml</code> sets <code>true</code> so the tests do not depend on <code>.env</code></td></tr>
                <tr><td><code>SSTOCK_REORDER_EXCLUDE_DAYS</code></td><td>Batches expiring within N days do not count as available stock for the Reorder Point (default 0)</td></tr>
                <tr><td><code>config/devlib.php</code></td><td><code>back_history = false</code> (<code>DL_BACK_HISTORY</code>): the Back button goes to the module grid, not the previous screen; <code>translate_fixed = true</code></td></tr>
            </tbody>
        </table>

        <h3>Deployment checklist</h3>
        <ol class="g-steps">
            <li>Clone the repository next to <code>../devlib</code> (<code>telasih/devlib</code> is a path repository without a remote) and run <code>composer setup</code>.</li>
            <li>Set <code>APP_ENV=production</code>, <code>APP_DEBUG=false</code>, real SMTP, and a strong <code>DB_PASSWORD</code>.</li>
            <li>Replace the seeded test passwords (or do not seed accounts); keep <code>SSTOCK_SAMPLE_REFRESH</code> off.</li>
            <li>Run <code>npm run build</code> after any CSS or JS change and hard-reload the browser.</li>
            <li>Help files live in <code>resources/</code> and are served through <code>auth</code> routes; never put them in <code>public/</code>.</li>
        </ol>

        <h3 id="testing">Testing</h3>
        <p>Tests run on in-memory SQLite (<code>phpunit.xml</code>: <code>DB_CONNECTION=sqlite</code>, <code>DB_DATABASE=:memory:</code>, array cache and session), so they never touch the MySQL database. <code>Tests\TestCase</code> disables Vite (no <code>npm run build</code> needed) and clears the static <code>Constants</code> cache between tests. <code>makeItem()</code> creates a product with its batch.</p>
        <pre class="g-code">php artisan test                     # everything
php artisan test --filter=HelpTest   # one class; use one --filter per command
composer test                        # config:clear, then php artisan test</pre>
        <table class="g-table">
            <thead><tr><th>Area</th><th>Test classes (<code>tests/Feature</code>, one in <code>tests/Unit</code>)</th></tr></thead>
            <tbody>
                <tr><td>Documents and ledger</td><td><code>DocumentLifecycleTest</code>, <code>StockFlowTest</code>, <code>TransactionalRulesTest</code>, <code>DoublePostingGuardTest</code>, <code>NumberingRaceTest</code>, <code>PreciseValueTest</code>, <code>ItemIntegrityMigrationTest</code>, <code>IssuanceStockUnitTest</code>, <code>DocumentReportsShowEntriesTest</code></td></tr>
                <tr><td>Items, units, masters</td><td><code>ItemCodeTest</code>, <code>ProductUnitTest</code>, <code>ProductUnitRulesTest</code>, <code>UnitTest</code>, <code>SupplierTest</code>, <code>KontanSupplierTest</code>, <code>QuickAddEndpointsTest</code>, <code>QuickAddScreensTest</code></td></tr>
                <tr><td>Re-order and lead time</td><td><code>ReorderOrderRecordTest</code>, <code>ReorderOrderClosingTest</code>, <code>ReorderOrderInfoTest</code>, <code>ReorderOrderHistoryTest</code>, <code>LeadTimeTest</code></td></tr>
                <tr><td>Stok Opname</td><td><code>StockCountEngineTest</code>, <code>StockCountEntryTest</code>, <code>StockCountReviewTest</code>, <code>StockCountAuditTest</code>, <code>StockCountPrintTest</code>, <code>StockCountScheduleTest</code>, <code>StockCountStartScreensTest</code>, <code>StockCountHelpTest</code>, <code>Unit/StockCountLineTest</code></td></tr>
                <tr><td>Access and session</td><td><code>SecurityTest</code>, <code>IdleLogoutTest</code>, <code>NoRememberMeTest</code>, <code>PasswordResetTest</code>, <code>BackButtonTest</code></td></tr>
                <tr><td>Company header</td><td><code>CompanyTest</code> (access, validation, logo upload and serving, three styles on documents, reports and the count sheet, defaults, group migration)</td></tr>
                <tr><td>Seeds, config, help</td><td><code>SeedersTest</code>, <code>SampleDataTest</code>, <code>EnvExampleTest</code>, <code>WallpapersTest</code>, <code>HelpTest</code> (module list against help anchors, anchors against the table of contents, role guards, glossary, diagrams)</td></tr>
            </tbody>
        </table>

        <h2 id="decisions">11. Key decisions and why</h2>
        <table class="g-table">
            <thead><tr><th>Decision</th><th>Why</th></tr></thead>
            <tbody>
                <tr><td>Stock changes only in <code>StockLedger</code>; <code>stock_qty</code> and <code>avg_cost</code> are not mass-assignable</td><td>One auditable code path; a stray <code>update()</code> cannot desynchronise the balance from the ledger</td></tr>
                <tr><td>Ledger rows are never edited; Cancel adds reversal rows (<code>reversal_of</code>)</td><td>Full history and an honest Stock Card; the original posting stays visible</td></tr>
                <tr><td>Save creates a draft; stock moves on Confirm</td><td>Typing mistakes cost nothing until the user commits; a confirmed document is locked</td></tr>
                <tr><td>Cancel needs a reason, a reversibility check, and a Supervisor for non-Supervisors</td><td>Control without blocking daily work; stock must never go negative, so an impossible cancel is refused and corrected with an Adjustment</td></tr>
                <tr><td>Stok Opname is a separate module that ends in ONE auto-approved ADJ document, and the transaction lock sits in <code>StockLedger</code></td><td>The routine count is a different job from an ad-hoc Adjustment, so the Adjustment is untouched; the ledger stays the single writer of stock and the single place that can refuse a posting while a count runs</td></tr>
                <tr><td>Incoming units belong to the product; global conversions are only a fallback</td><td>Pack sizes differ per product (1 box can be 12 pcs for one item and 24 for another), so the factor lives with the product. The older global pair still works for units without a product factor</td></tr>
                <tr><td>Quick add posts to the normal <code>store</code> routes and uses the same module right</td><td>One validation path and one permission rule; the plus button is not rendered for users who could not save that master on its own screen</td></tr>
                <tr><td>A re-order order closes by quantity, not by any Receiving</td><td>A partial delivery must not hide a missing quantity. <code>reconcile</code> depends only on receiving data, so confirm and cancel can both call it safely</td></tr>
                <tr><td>One class (<code>Company</code>) decides every print header</td><td>Documents, count sheets and reports must never disagree about the company name; DevLib already decides the look (<code>Kop</code>), so the app only supplies data</td></tr>
                <tr><td>Kontan is a protected system supplier</td><td>A Receiving always names a supplier, so reports stay complete, and cash purchases do not invent one-off suppliers</td></tr>
                <tr><td>Idle logout = browser timer + server session lifetime</td><td>The warning needs a client timer. The server session (<code>SESSION_LIFETIME</code>) is the real limit and the ping route extends it only while the user is active</td></tr>
                <tr><td>Small settings in a key / value table (<code>constants</code>)</td><td>The Stok Opname schedule and the company header need no migration per setting. Reads are cached per request and safe when the table is missing</td></tr>
                <tr><td>Adjustment needs approval and a stale-count check</td><td>Opname differences move value; the approver must be sure the count still matches the system</td></tr>
                <tr><td>Moving average cost</td><td>Simple, no cost layers; the weekly value chart reads <code>balance_qty x balance_avg_cost</code> from the ledger</td></tr>
                <tr><td>Access = module list x role x group, with RO</td><td>One row in <code>Modules</code> adds a screen to menu, groups and route guard at once</td></tr>
                <tr><td>Documents copy recipient and department names</td><td>Old documents stay readable after master data is renamed</td></tr>
                <tr><td>Master data is deactivated, never deleted</td><td>History and foreign keys stay valid</td></tr>
                <tr><td>Product / batch model, expiry not in the code, FEFO warning only, Reorder Point per product</td><td>Matches GS1 / SAP practice; a warning avoids blocking a legitimate pick (see the <code>stok-batch-expiry</code> skill)</td></tr>
                <tr><td>Screens written in English, translated by <code>Indonesian</code></td><td>DevLib convention; the User's Guide is Indonesian plain HTML and this guide is English plain HTML, the only text outside the dictionary</td></tr>
                <tr><td>Region data copied from tmerp</td><td>No second region data set to maintain</td></tr>
                <tr><td>Sample data only in seeders, through the official path</td><td>Repeatable, and stock always equals the ledger</td></tr>
                <tr><td>Help in <code>resources/</code>, diagrams as inline SVG, Technical Guide behind the <code>administrator</code> middleware</td><td>Not reachable without login, diagrams follow the theme, hiding a link alone is not security</td></tr>
            </tbody>
        </table>

        <h2 id="open">12. Open items</h2>
        <ul class="g-list">
            <li>Stok Opname: colour the large-difference rows (needs a DevLib <code>x-dl::sheet</code> option), a backup Save button on the entry sheet (today only auto-save), and refreshing one moved line instead of all moved lines.</li>
            <li>Re-order records: no screen to edit or delete an order (only <i>Close Order</i> exists). The list and detail are in Riwayat Pemesanan.</li>
            <li>Segregation of duties between creator and approver (Adjustment, Stok Opname).</li>
            <li>Hide the Save button on RO modules (the server already rejects the save).</li>
            <li>Company screen: no separate black-and-white logo for Ink Saver (the same PNG is used), and no second letterhead for a VAT variant (<code>companyPpn</code>).</li>
            <li>Regenerate the reference DDL; production SMTP; portrait wallpapers for phones.</li>
        </ul>
    </main>
</div>
@endsection
