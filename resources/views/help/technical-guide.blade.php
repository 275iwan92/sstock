{{-- REMARK
 Tujuan       : Technical Guide Stoku (HTML, English): flow besar sistem, siklus dokumen, buku besar stok, model data, akses, konvensi, operasional.
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
        <a href="#open">11. Open items</a>
    </nav>

    <main class="g-body">
        <h1>Stoku Technical Guide</h1>
        <p class="g-lead">How Stoku works from login to report: where stock is entered, how it is posted, who may do what, and where each rule lives in the code. Administrator access only.</p>

        <h2 id="overview">1. System overview</h2>
        <p>Stoku is a simple inventory application. <b>Master data</b> is defined once, <b>stock documents</b> record every change, the <b>ledger</b> holds the balances, and <b>reports plus the dashboard</b> read the result.</p>
        <table class="g-table">
            <thead><tr><th>Layer</th><th>What it is</th><th>Where</th></tr></thead>
            <tbody>
                <tr><td>Stack</td><td>Laravel 13, PHP 8.4, MySQL 8 (tests: in-memory SQLite), Vite + Tailwind 4</td><td><code>composer.json</code>, <code>vite.config.js</code></td></tr>
                <tr><td>UI</td><td>DevLib components (<code>x-dl::*</code>, package <code>telasih/devlib</code>); screens are declarations, not hand-built markup</td><td><code>resources/views</code></td></tr>
                <tr><td>Translation</td><td>Screen labels are written in English and translated to Indonesian by an app dictionary</td><td><code>app/Support/Indonesian.php</code>, <code>lang/id</code></td></tr>
                <tr><td>Routing and guards</td><td>One route file; the <code>module</code> middleware checks module access per URL</td><td><code>routes/web.php</code>, <code>EnsureModuleAccess</code></td></tr>
                <tr><td>Business logic</td><td>Three services: document lifecycle, stock ledger, valuation</td><td><code>app/Services</code></td></tr>
                <tr><td>Data</td><td>Master tables, documents, ledger</td><td><code>database/migrations</code></td></tr>
            </tbody>
        </table>

        <h2 id="big-flow">2. The big flow</h2>
        <div class="g-flow">
            <div class="g-row g-row-3">
                <div class="g-box g-gray"><b>Login</b><small>AuthController</small></div>
                <div class="g-box g-gray"><b>Module access check</b><small>role + group + RO/RW</small></div>
                <div class="g-box g-gray"><b>Module menu</b><small>Modules + Menu</small></div>
            </div>
            <div class="g-arrow">&darr;</div>
            <div class="g-group">
                <div class="g-cap">User input: master data and stock documents</div>
                <div class="g-row g-row-5">
                    <div class="g-box g-teal"><b>Master data</b><small>items, suppliers, units</small></div>
                    <div class="g-box g-teal"><b>Opening balance</b><small>Supervisor</small></div>
                    <div class="g-box g-teal"><b>Receiving</b><small>from supplier</small></div>
                    <div class="g-box g-teal"><b>Issuance</b><small>to recipient</small></div>
                    <div class="g-box g-teal"><b>Adjustment</b><small>stock count, Approve</small></div>
                </div>
            </div>
            <div class="g-arrow">&darr;</div>
            <div class="g-box g-purple g-wide"><b>StockDocuments: document lifecycle</b><small>Draft, Confirm, Approve, Cancel. Stock does not change before Confirm.</small></div>
            <div class="g-arrow">&darr;</div>
            <div class="g-box g-purple g-wide"><b>StockLedger: the only code that changes stock</b><small>post, reverse, assertReversible; moving average cost</small></div>
            <div class="g-arrow">&darr;</div>
            <div class="g-row g-row-2">
                <div class="g-box g-purple"><b>items</b><small>stock_qty and avg_cost (balance)</small></div>
                <div class="g-box g-purple"><b>stock_movements</b><small>ledger, rows are never edited</small></div>
            </div>
            <div class="g-arrow">&darr;</div>
            <div class="g-row g-row-2">
                <div class="g-box g-gray"><b>Reports</b><small>stock card, summary, expiry</small></div>
                <div class="g-box g-gray"><b>Home screen</b><small>KPIs, reorder alerts, re-order log</small></div>
            </div>
        </div>
        <p class="g-note">A confirmed Receiving closes any open re-order record for the items it contains. Cancelling that Receiving reopens them.</p>

        <h2 id="business">3. Business flow</h2>
        <ol class="g-steps">
            <li><b>Set up master data.</b> Categories, locations, units with conversions, suppliers, departments, recipients, items. Item code is generated: <code>{CATEGORY PREFIX, 3 chars}-{4-digit sequence}[-{YYMM expiry}]</code> (<code>Item::nextCode</code>). Code, category and expiry are locked once saved. Departments and recipients can be added on the fly from inside a document form.</li>
            <li><b>Opening balance.</b> Supervisor only. Rejected for an item that already has stock movements (use Adjustment instead).</li>
            <li><b>Receiving.</b> A line may use another unit; it is converted to the item's stock unit by <code>UnitConverter</code>. A missing conversion is rejected.</li>
            <li><b>Issuance.</b> A recipient is required. Stock may never go negative.</li>
            <li><b>Adjustment.</b> The line quantity is the difference <code>counted_qty - system_qty</code>. It needs Supervisor approval.</li>
            <li><b>Reports.</b> All have a from/to date filter. Prices and values are visible to Supervisors only.</li>
        </ol>

        <h2 id="lifecycle">4. Document lifecycle</h2>
        <p>Four document types share one controller (<code>StockDocumentController</code>, routes <code>docs/{type}</code> with type <code>opening | receiving | issuance | adjustment</code>) and one service (<code>StockDocuments</code>). The controller reads <code>_action</code> from the form button: <code>save, confirm, delete, cancel, approve, reject</code>. Document numbers are <code>PREFIX-YYMM-NNNN</code> (OPN, RCV, ISS, ADJ), sequenced per type per month.</p>

        <h3>Opening balance, Receiving, Issuance</h3>
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

        <h3>Rules that apply to every document</h3>
        <ul class="g-list">
            <li><b>Draft</b> never touches stock. Only a draft can be edited or deleted. Confirm needs at least one line.</li>
            <li><b>Confirm</b> sets the document date/time to the moment of Confirm (Opening balance keeps the chosen date). A confirmed document is locked; correct it with an Adjustment.</li>
            <li><b>Approving an Adjustment</b> is refused if the item's stock changed since it was counted (<code>system_qty</code> differs). Reject it and count again.</li>
            <li><b>Cancel</b> needs a reason (3 to 255 characters). Before accepting a cancel or a request, <code>StockLedger::assertReversible</code> computes whether stock is still enough to pull the document back. If not, the cancel is refused and an Adjustment is needed. The result is also shown as a warning on the form before the button is pressed.</li>
            <li><b>Cancel authority:</b> a Supervisor or Administrator cancels directly. Others request. Only the requester or a Supervisor may withdraw a pending request (403 for anyone else).</li>
            <li>The Confirm, Delete, Cancel, Approve and Reject buttons come from the DevLib form status (<code>x-dl::form kind="document" :pipeline="true"</code>). They are not built by hand. Every stage is written to the form log.</li>
        </ul>

        <h2 id="ledger">5. Stock ledger</h2>
        <p>Only <code>App\Services\StockLedger</code> may change <code>items.stock_qty</code> and <code>items.avg_cost</code>. The columns are deliberately not mass-assignable; the ledger writes them with <code>forceFill</code>.</p>
        <ol class="g-steps">
            <li><b>Lock</b> the item row (<code>lockForUpdate</code>) for each document line.</li>
            <li><b>Signed quantity:</b> Issuance is negative; Opening and Receiving positive; Adjustment carries its own sign. A zero difference is skipped.</li>
            <li><b>Guard:</b> a resulting balance below zero throws a validation error (insufficient stock).</li>
            <li><b>Cost:</b> additions from Receiving or Opening use the line cost; positive Adjustments use the current average cost (or the item price if there is none); removals use the average cost.</li>
            <li><b>Moving average:</b> <code>(stock x avg + qty x cost) / new balance</code> on additions.</li>
            <li><b>Write:</b> update the item and insert a <code>stock_movements</code> row with the balance after the movement. Items at or below the Reorder Point are returned and shown as an alert on the form.</li>
        </ol>
        <ul class="g-list">
            <li><code>stock_movements</code> rows are never edited or deleted. <code>reverse()</code> adds new rows with <code>reversal_of</code> pointing at the original; the Stock Card marks them "(CANCELLED)".</li>
            <li><code>StockValuation::series</code> builds the weekly stock-value chart from <code>balance_qty x balance_avg_cost</code> in the ledger (Supervisor only).</li>
        </ul>

        <h2 id="data">6. Data model</h2>
        <table class="g-table">
            <thead><tr><th>Table</th><th>Purpose</th><th>Key relations</th></tr></thead>
            <tbody>
                <tr><td><code>categories</code>, <code>locations</code>, <code>units</code>, <code>unit_conversions</code></td><td>Item classification, storage places, units and conversion factors</td><td>items belong to a category; <code>items.location</code> stores the location <b>code</b></td></tr>
                <tr><td><code>suppliers</code> (+ <code>geo_*</code>)</td><td>Vendors with contact and region (region tables copied from tmerp)</td><td>Receiving documents</td></tr>
                <tr><td><code>departments</code>, <code>recipients</code></td><td>Who receives goods; a recipient has a default department</td><td>Documents copy the <b>names</b> so they stay readable if master data changes</td></tr>
                <tr><td><code>items</code></td><td>Item master plus running balance <code>stock_qty</code> and <code>avg_cost</code> (cache of the ledger)</td><td>category; lines; movements; re-order records</td></tr>
                <tr><td><code>stock_documents</code></td><td>Header: number, type, status, supplier/recipient, reason, created/confirmed/approved/cancelled by and at, cancel request</td><td>creator and approver users; lines; movements</td></tr>
                <tr><td><code>stock_document_lines</code></td><td>Item, quantity, cost; Adjustment keeps <code>system_qty</code> and <code>counted_qty</code>; <code>entered_qty/unit/unit_cost</code> keep what was typed when the unit differs</td><td>document, item</td></tr>
                <tr><td><code>stock_movements</code></td><td>The ledger: quantity, cost, balance after, user, <code>reversal_of</code></td><td>item, document, itself (reversal)</td></tr>
                <tr><td><code>reorder_orders</code></td><td>Re-order log; closed by a confirmed Receiving (<code>receiving_id</code>)</td><td>item, receiving document</td></tr>
                <tr><td><code>users</code>, <code>user_groups</code>, <code>user_group_modules</code></td><td>Accounts, groups, and per-module access (none / RO / RW)</td><td>user belongs to a group</td></tr>
            </tbody>
        </table>

        <h2 id="access">7. Access and security</h2>
        <ol class="g-steps">
            <li>A request must be authenticated (<code>auth</code>), otherwise it goes to Login.</li>
            <li><code>EnsureModuleAccess</code> maps the route to a module code with <code>Modules::forRoute</code>. Access needs <b>both</b> a sufficient role and the module granted by the user's group.</li>
            <li>A module with RO access refuses every non-GET request.</li>
            <li>The controller or form request then checks per-document-type rights.</li>
        </ol>
        <ul class="g-list">
            <li>Roles: <code>data_entry</code> &lt; <code>supervisor</code> &lt; <code>administrator</code>. Administrators always have full access. A user without a group gets the role default (<code>Modules::defaults</code>).</li>
            <li><code>App\Support\Modules</code> is the single module list; it feeds the menu, the group settings, the module list screen and the route guard. A new module is one new row there.</li>
            <li>Extra middleware: <code>supervisor</code> (edit items, units, categories, locations, adjustment report) and <code>administrator</code> (users, groups, modules, this guide).</li>
            <li>Approve and Reject are Supervisor only. Password reset uses an e-mail link (throttled to 5 per minute, valid 60 minutes, single use).</li>
            <li>Known gaps: no segregation of duties (the creator of an Adjustment may approve it); the Save button still shows on RO modules (the server rejects it).</li>
        </ul>

        <h2 id="screens">8. Dashboard and reports</h2>
        <ul class="g-list">
            <li><b>Home screen</b> (<code>HomeController</code>): clickable KPIs, Reorder Point alert with order status, near-expiry alert, approval queue and stock-value chart (Supervisor).</li>
            <li><b>Re-order log:</b> "Record order" creates a <code>reorder_orders</code> row; a confirmed Receiving closes it automatically.</li>
            <li><b>Reports</b> (<code>ReportController</code>): Stock Card, Stock Summary, Receiving, Issuance (confirmed and cancel-pending documents only), Expiry, Opname &amp; Adjustment (Supervisor).</li>
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
composer sample    # migrate:fresh --seed (ERASES the database), then run sstock:import-geo
php artisan test   # in-memory SQLite
vendor/bin/pint --dirty
npm run build      # required after editing resources/css/app.css</pre>
        <ul class="g-list">
            <li>Test accounts (development only; change before production): <code>admin</code>, <code>supervisor</code>, <code>entry</code> (see <code>UserSeeder</code>).</li>
            <li>Key tests: <code>DocumentLifecycleTest</code> (Confirm, Cancel, approval), <code>StockFlowTest</code> (stock flow), <code>SecurityTest</code> (access), <code>SeedersTest</code> (idempotent seeders, stock equals the ledger), <code>ItemCodeTest</code>.</li>
            <li>Production: set SMTP (<code>MAIL_*</code>, currently <code>log</code>) and replace the test passwords. <code>database/sql/sstock_mysql_ddl.sql</code> is out of date; migrations are the source of truth.</li>
        </ul>

        <h2 id="open">11. Open items</h2>
        <ul class="g-list">
            <li>Print a single document (Receiving or Issuance note).</li>
            <li>Segregation of duties between creator and approver.</li>
            <li>Expected arrival of orders from <code>lead_time_days</code>; cancel or edit a re-order record.</li>
            <li>Regenerate the reference DDL; production SMTP; portrait wallpapers for phones.</li>
        </ul>
    </main>
</div>
@endsection
