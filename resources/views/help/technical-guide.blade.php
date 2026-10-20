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
                <tr><td>Stack</td><td>Laravel 13, PHP 8.4, MySQL 8 (tests: in-memory SQLite), Vite + Tailwind 4</td><td><code>composer.json</code>, <code>vite.config.js</code></td></tr>
                <tr><td>UI</td><td>DevLib components (<code>x-dl::*</code>, package <code>telasih/devlib</code>); screens are declarations, not hand-built markup</td><td><code>resources/views</code></td></tr>
                <tr><td>Translation</td><td>Screen labels are written in English and translated to Indonesian by an app dictionary</td><td><code>app/Support/Indonesian.php</code>, <code>lang/id</code></td></tr>
                <tr><td>Routing and guards</td><td>One route file; the <code>module</code> middleware checks module access per URL</td><td><code>routes/web.php</code>, <code>EnsureModuleAccess</code></td></tr>
                <tr><td>Business logic</td><td>Three services: document lifecycle, stock ledger, valuation</td><td><code>app/Services</code></td></tr>
                <tr><td>Data</td><td>Master tables, documents, ledger</td><td><code>database/migrations</code></td></tr>
            </tbody>
        </table>

        <h2 id="big-flow">2. The big flow</h2>
        @include('help.diagram', ['name' => 'tg-system-flow', 'caption' => 'System flow: from login to reports. StockLedger is the only writer of stock.'])
        <p class="g-note">A confirmed Receiving closes any open re-order record for the products it contains (any batch counts). Cancelling that Receiving reopens them.</p>

        <h3>Product and batch model</h3>
        @include('help.diagram', ['name' => 'tg-product-batch', 'caption' => 'Product and batch model: tables, document lines, FEFO allocation and the per-product reorder point.'])

        <h2 id="business">3. Business flow</h2>
        <ol class="g-steps">
            <li><b>Set up master data.</b> Categories, locations, units with conversions, suppliers, departments, recipients, items. Items are two-level: a <b>product</b> (<code>products</code>, stable code <code>{CATEGORY PREFIX, 3 chars}-{4-digit sequence}</code> from <code>Product::nextCode</code>, never contains a date) and its <b>batches</b> (<code>items</code>, one per expiry date, batch code <code>{product code}-{YYMMDD}</code>; one undated batch with the product code when the product does not expire). Code, category and <code>tracks_expiry</code> are locked once saved; any user may add a product, only a Supervisor edits parameters (propagated to the batches by <code>Product::propagate</code>). Departments and recipients can be added on the fly from inside a document form.</li>
            <li><b>Opening balance.</b> Supervisor only. Rejected for an item that already has stock movements (use Adjustment instead).</li>
            <li><b>Receiving.</b> A line names a product and, for <code>tracks_expiry</code> products, an expiry date (required): <code>Product::batchFor()</code> finds or creates the batch (unused batches of a deleted or edited draft are purged). A line may use another unit; it is converted to the item's stock unit by <code>UnitConverter</code>. Issuance lines carry a unit too (same conversion, no price; split FEFO parts get a proportional <code>entered_qty</code>). <code>StockDocuments::assertConversions()</code> collects every missing pair for Receiving / Issuance, flashes <code>missing_conversions</code> and rejects the save; the form opens the "missing conversion" modal (<code>docs/conversion-modal</code>) which posts each pair to <code>docs.conversion</code> (any user with write access to RCV / ISS; <code>updateOrCreate</code> on <code>unit_conversions</code>), then the user saves again.</li>
            <li><b>Issuance.</b> A recipient is required. Stock may never go negative. A line names a batch; a quantity above that batch's stock spills over to the other usable batches of the same product (earliest expiry first, FEFO; <code>StockDocuments::allocate</code>). Picking a later-expiring batch while an earlier one has stock only warns (<code>fefoWarnings</code>), it never blocks.</li>
            <li><b>Adjustment.</b> The line quantity is the difference <code>counted_qty - system_qty</code>. It needs Supervisor approval (single level; multi-level authority is deliberately not used in the stock app, it belongs to purchasing and sales).</li>
            <li><b>Reports.</b> All have a from/to date filter. Prices and values are visible to Supervisors only.</li>
        </ol>

        <h2 id="lifecycle">4. Document lifecycle</h2>
        <p>Four document types share one controller (<code>StockDocumentController</code>, routes <code>docs/{type}</code> with type <code>opening | receiving | issuance | adjustment</code>) and one service (<code>StockDocuments</code>). The controller reads <code>_action</code> from the form button: <code>save, confirm, delete, cancel, approve, reject</code>. Document numbers are <code>PREFIX-YYMM-NNNN</code> (OPN, RCV, ISS, ADJ), sequenced per type per month.</p>

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
        @include('help.diagram', ['name' => 'tg-stock-ledger', 'caption' => 'What StockLedger::post does for every document line, and how Cancel reverses it.'])
        <ol class="g-steps">
            <li><b>Lock</b> the item row (<code>lockForUpdate</code>) for each document line.</li>
            <li><b>Signed quantity:</b> Issuance is negative; Opening and Receiving positive; Adjustment carries its own sign. A zero difference is skipped.</li>
            <li><b>Guard:</b> a resulting balance below zero throws a validation error (insufficient stock).</li>
            <li><b>Cost:</b> additions from Receiving or Opening use the line cost; positive Adjustments use the current average cost (or the item price if there is none); removals use the average cost.</li>
            <li><b>Moving average:</b> <code>(stock x avg + qty x cost) / new balance</code> on additions.</li>
            <li><b>Write:</b> update the item and insert a <code>stock_movements</code> row with the balance after the movement. Products at or below the Reorder Point are returned and shown as an alert on the form. The reorder check is per <b>product</b> on the available stock (sum of batches with no expiry or expiry on/after today plus <code>sstock.reorder_exclude_days</code>), so expired batches never count as available.</li>
        </ol>
        <ul class="g-list">
            <li><code>stock_movements</code> rows are never edited or deleted. <code>reverse()</code> adds new rows with <code>reversal_of</code> pointing at the original; the Stock Card marks them "(CANCELLED)".</li>
            <li><code>StockValuation::series</code> builds the weekly stock-value chart from <code>balance_qty x balance_avg_cost</code> in the ledger (Supervisor only).</li>
        </ul>

        <h2 id="data">6. Data model</h2>
        @include('help.diagram', ['name' => 'tg-erd', 'caption' => 'Entity relationship diagram: key columns and relations.'])
        <table class="g-table">
            <thead><tr><th>Table</th><th>Purpose</th><th>Key relations</th></tr></thead>
            <tbody>
                <tr><td><code>categories</code>, <code>locations</code>, <code>units</code>, <code>unit_conversions</code></td><td>Item classification, storage places, units and conversion factors</td><td>items belong to a category; <code>items.location</code> stores the location <b>code</b></td></tr>
                <tr><td><code>suppliers</code> (+ <code>geo_*</code>)</td><td>Vendors with contact and region (region tables copied from tmerp)</td><td>Receiving documents</td></tr>
                <tr><td><code>departments</code>, <code>recipients</code></td><td>Who receives goods; a recipient has a default department</td><td>Documents copy the <b>names</b> so they stay readable if master data changes</td></tr>
                <tr><td><code>products</code></td><td>Item master: stable code, parameters (price, reorder point/qty, lead time), <code>tracks_expiry</code>. The single source of the parameters</td><td>category; batches (<code>items</code>); re-order records</td></tr>
                <tr><td><code>items</code></td><td>Batches: batch code, <code>expiry_date</code>, running balance <code>stock_qty</code> and <code>avg_cost</code> (cache of the ledger); name/unit/parameters are copies of the product for readers</td><td>product; lines; movements</td></tr>
                <tr><td><code>stock_documents</code></td><td>Header: number, type, status, supplier/recipient, reason, created/confirmed/approved/cancelled by and at, cancel request</td><td>creator and approver users; lines; movements</td></tr>
                <tr><td><code>stock_document_lines</code></td><td>Item, quantity, cost; Adjustment keeps <code>system_qty</code> and <code>counted_qty</code>; <code>entered_qty/unit/unit_cost</code> keep what was typed when the unit differs</td><td>document, item</td></tr>
                <tr><td><code>stock_documents.reversal_of_document_id</code></td><td>Set on the auto-created reversing ADJUSTMENT document (status approved) when a processed document is cancelled (<code>StockDocuments::recordReversal()</code>); its lines carry the ledger delta, system and resulting quantity. The ledger rows stay <code>reversal_of</code> on the original document, so the Stock Card is unchanged. The reversing document cannot be cancelled.</td><td>self</td></tr>
                <tr><td><code>stock_movements</code></td><td>The ledger: quantity, cost, balance after, user, <code>reversal_of</code></td><td>item, document, itself (reversal)</td></tr>
                <tr><td><code>reorder_orders</code></td><td>Re-order log per product; closed by a confirmed Receiving of any of its batches (<code>receiving_id</code>)</td><td>product, receiving document</td></tr>
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
                <tr><td><code>add_is_active_to_user_groups</code></td><td>Activate / Deactivate for groups</td></tr>
                <tr><td><code>give_expiry_report_to_groups</code>, <code>give_sample_data_to_administrator_groups</code></td><td>Data migrations: grant the new modules to existing administrator groups</td></tr>
                <tr><td><code>create_products_and_batches</code></td><td>Two-level item model: <code>products</code>, <code>items.product_id</code> (batches), <code>reorder_orders.product_id</code>; old items are merged into products</td></tr>
            </tbody>
        </table>

        <h2 id="access">7. Access and security</h2>
        @include('help.diagram', ['name' => 'tg-access-flow', 'caption' => 'Login and per-request authorization.'])
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
            <li>Print: <code>docs.print</code> (<code>StockDocumentPrint</code> on DevLib <code>x-dl::print-doc</code>) is available in every status and prints the MODE (stamp + watermark for non-valid modes). Approve and Reject are Supervisor only. Password reset uses an e-mail link (throttled to 5 per minute, valid 60 minutes, single use).</li>
            <li>Known gaps: no segregation of duties (the creator of an Adjustment may approve it); the Save button still shows on RO modules (the server rejects it).</li>
        </ul>

        <h3>Module list (<code>App\Support\Modules</code>)</h3>
        <table class="g-table">
            <thead><tr><th>Code</th><th>Menu</th><th>Group</th><th>Minimum role</th><th>Guarded route prefix</th></tr></thead>
            <tbody>
                <tr><td>ITM</td><td>Barang</td><td>Data Master</td><td>Data Entry</td><td><code>products.</code> (add: any role; edit and toggle: Supervisor)</td></tr>
                <tr><td>SUP, DEP, RCP</td><td>Suppliers, Departemen, Penerima</td><td>Data Master</td><td>Data Entry</td><td><code>suppliers.</code>, <code>departments.</code>, <code>recipients.</code></td></tr>
                <tr><td>UOM, CAT, LOC</td><td>Satuan, Kategori, Lokasi Penyimpanan</td><td>Data Master</td><td>Supervisor</td><td><code>units.</code>, <code>categories.</code>, <code>locations.</code></td></tr>
                <tr><td>OPN, ADJ</td><td>Saldo Awal, Penyesuaian Stok</td><td>Transaksi</td><td>Supervisor</td><td><code>docs.*</code> with <code>{type}</code> = opening / adjustment</td></tr>
                <tr><td>RCV, ISS</td><td>Penerimaan Barang, Pengeluaran Barang</td><td>Transaksi</td><td>Data Entry</td><td><code>docs.*</code> with <code>{type}</code> = receiving / issuance</td></tr>
                <tr><td>R-CARD, R-SUM, R-IN, R-OUT, R-EXP</td><td>Kartu Stok, Ringkasan Stok, Laporan Penerimaan / Pengeluaran / Kedaluwarsa</td><td>Laporan</td><td>Data Entry</td><td><code>reports.*</code></td></tr>
                <tr><td>R-ADJ</td><td>Laporan Opname &amp; Penyesuaian</td><td>Laporan</td><td>Supervisor</td><td><code>reports.adjustment</code></td></tr>
                <tr><td>USR, GRP, SMP, MOD</td><td>Pengguna, Grup, Data Contoh, Daftar Modul</td><td>Keamanan</td><td>Administrator</td><td><code>users.</code>, <code>groups.</code>, <code>sample.</code>, <code>modules.</code></td></tr>
            </tbody>
        </table>
        <p class="g-note">Routes that belong to no module (home, logout, re-order records, Help) are open to every logged-in user. The User's Guide lists only the modules the reader may open (<code>User::canAccess</code>); the Technical Guide route sits in the <code>administrator</code> middleware group and its tab link is rendered only for Administrators.</p>

        <h2 id="screens">8. Dashboard and reports</h2>
        <ul class="g-list">
            <li><b>Home screen</b> (<code>HomeController</code>): clickable KPIs, Reorder Point alert with order status, near-expiry alert, approval queue and stock-value chart (Supervisor).</li>
            <li><b>Re-order log:</b> "Record order" creates a <code>reorder_orders</code> row; a confirmed Receiving closes it automatically.</li>
            <li><b>Reports</b> (<code>ReportController</code>): Stock Card, Stock Summary (reorder status per product from the closing balance of non-expired batches; Expiry stays per batch), Receiving, Issuance (confirmed and cancel-pending documents only), Expiry, Opname &amp; Adjustment (Supervisor).</li>
        </ul>

        @include('help.diagram', ['name' => 'tg-reorder-flow', 'caption' => 'Re-order flow: alert, Record Order, and closing by Receiving.'])

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
composer sample    # migrate:fresh --seed --force (ERASES the database including geo_*); then run sstock:import-geo
php artisan test   # in-memory SQLite
vendor/bin/pint --dirty
npm run build      # required after editing resources/css/app.css</pre>
        <ul class="g-list">
            <li>Test accounts (development only; change before production): <code>admin</code>, <code>supervisor</code>, <code>entry</code> (see <code>UserSeeder</code>).</li>
            <li>Key tests: <code>DocumentLifecycleTest</code> (Confirm, Cancel, approval), <code>StockFlowTest</code> (stock flow), <code>SecurityTest</code> (access), <code>SeedersTest</code> (idempotent seeders, stock equals the ledger), <code>ItemCodeTest</code>.</li>
            <li>Production: set SMTP (<code>MAIL_*</code>, currently <code>log</code>) and replace the test passwords. <code>database/sql/sstock_mysql_ddl.sql</code> is out of date; migrations are the source of truth.</li>
        </ul>

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
                <tr><td><code>APP_LOCALE=id</code></td><td>Indonesian screens (dictionary active only for <code>id</code>)</td></tr>
                <tr><td><code>DB_*</code>, <code>DB_ADMIN_*</code></td><td>Application MySQL account; admin account used only by the installer and <code>sstock:import-geo</code></td></tr>
                <tr><td><code>MAIL_*</code></td><td><code>MAIL_MAILER=log</code> in development (reset links only appear in <code>storage/logs/laravel.log</code>); production needs SMTP</td></tr>
                <tr><td><code>SSTOCK_SAMPLE_REFRESH</code></td><td>Allow the Sample Data screen (default on outside production)</td></tr>
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

        <h2 id="decisions">11. Key decisions and why</h2>
        <table class="g-table">
            <thead><tr><th>Decision</th><th>Why</th></tr></thead>
            <tbody>
                <tr><td>Stock changes only in <code>StockLedger</code>; <code>stock_qty</code> and <code>avg_cost</code> are not mass-assignable</td><td>One auditable code path; a stray <code>update()</code> cannot desynchronise the balance from the ledger</td></tr>
                <tr><td>Ledger rows are never edited; Cancel adds reversal rows (<code>reversal_of</code>)</td><td>Full history and an honest Stock Card; the original posting stays visible</td></tr>
                <tr><td>Save creates a draft; stock moves on Confirm</td><td>Typing mistakes cost nothing until the user commits; a confirmed document is locked</td></tr>
                <tr><td>Cancel needs a reason, a reversibility check, and a Supervisor for non-Supervisors</td><td>Control without blocking daily work; stock must never go negative, so an impossible cancel is refused and corrected with an Adjustment</td></tr>
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
            <li>Print a single document (Receiving or Issuance note).</li>
            <li>Segregation of duties between creator and approver.</li>
            <li>Expected arrival of orders from <code>lead_time_days</code>; cancel or edit a re-order record.</li>
            <li>Regenerate the reference DDL; production SMTP; portrait wallpapers for phones.</li>
        </ul>
    </main>
</div>
@endsection
