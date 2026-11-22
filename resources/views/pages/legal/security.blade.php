<x-marketing-layout title="Security">
    <div class="max-w-4xl mx-auto py-16 px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Security</h1>
            <p class="text-lg text-gray-600">How Kipit POS protects shop data — and what you must do on each POS machine.</p>
        </div>

        <div class="prose prose-lg text-gray-600 max-w-none">
            <p><strong>Last Updated:</strong> {{ date('Y-m-d') }}</p>

            <h2 class="text-2xl font-bold text-gray-800 mt-10 mb-4">Who we are</h2>
            <p>
                Kipit POS is the brand behind this multi-tenant Point of Sale product.
                For product support, email
                <a href="mailto:support@kipitpos.com" class="text-kipit-primary hover:underline">support@kipitpos.com</a>.
                For security reports, see the contact section below.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-10 mb-4">What clients must do (required for integrity)</h2>
            <p>
                Kipit Desktop hardens the offline database and detects tampering, but the OS and people who use the POS machine still matter.
                Treat the items below as part of running a secure shop — not optional extras.
            </p>
            <ul class="list-disc pl-6 space-y-2">
                <li>
                    <strong>Turn on full-disk encryption</strong> on every POS PC:
                    <strong>BitLocker</strong> (Windows Pro/Enterprise), <strong>FileVault</strong> (macOS), or the equivalent on Linux.
                    Without disk encryption, anyone with physical access can copy files from the drive even if Kipit is closed.
                </li>
                <li>
                    <strong>Use a strong Windows/macOS login password</strong> and lock the screen whenever staff step away.
                    Prefer separate OS accounts per cashier where practical; do not share an admin OS account for day-to-day POS use.
                </li>
                <li>
                    <strong>Do not browse or edit AppData / Kipit data folders</strong> (or copy them to USB sticks “for backup”).
                    Local shop data lives under the OS application-data directory. Manual edits break integrity checks and can force a cloud repair that discards local-only changes.
                </li>
                <li>
                    <strong>Keep the machine online regularly</strong> so sync can run. Integrity failures trigger a mandatory
                    <strong>cloud-wins repair</strong>: local sales/inventory rows that fail the check are replaced from the server.
                    Offline-only edits that never synced may be lost after a repair — sync before major maintenance.
                </li>
                <li>
                    <strong>Before installing a desktop update:</strong> connect to the internet and wait until the sync indicator
                    shows idle/synced. App upgrades refresh the integrity fingerprint automatically so a normal update does not
                    purge local data; syncing first still protects any sales that only exist on that PC.
                </li>
                <li>
                    <strong>Revoke lost devices immediately</strong> in Kipit POS (web) → <strong>Settings → Connected Devices</strong>,
                    then change the user’s password. Kipit does not remotely wipe a stolen hard drive.
                </li>
                <li>
                    <strong>Keep Kipit Desktop updated</strong> and install OS security patches. Only install the desktop app from official Kipit channels.
                </li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-800 mt-10 mb-4">Roles and desktop access</h2>
            <p>
                Shop admins configure roles under <strong>Users → Roles</strong>.
                <strong>Web app permissions</strong> (guard <code>web</code>) and
                <strong>Desktop app permissions</strong> (guard <code>desktop</code>) are separate.
                The desktop app enforces the desktop set from its local SQLite database while offline;
                role/permission changes sync as offline events when the device is back online.
                Mobile app permissions (guard <code>sanctum</code>) remain available for a future mobile client.
                Connecting a desktop device requires desktop <code>access desktop</code>;
                managing lost devices requires <code>access connected devices</code> (owners always can).
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-10 mb-4">Multi-tenant isolation</h2>
            <p>
                Each shop (tenant) uses its own database. API and app traffic is scoped by the shop’s domain:
                when a request hits a shop host, Kipit POS loads only that shop’s database connection.
                Tenant APIs require authentication (Bearer token). Shop data is not shared across tenants by design.
            </p>
            <p>
                Isolation is database-per-tenant (not a shared table with a shop ID filter). That reduces the classic
                “wrong tenant_id in a query” class of bugs, though like any system it still depends on correct
                domain routing and server configuration.
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-10 mb-4">Offline desktop storage</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li>
                    The desktop app keeps a <strong>local SQLite database</strong> so sales and inventory can continue offline.
                    When the app is closed, the database is stored as <strong>sealed ciphertext</strong>
                    (<code>database.sealed</code> (AES-256-GCM) with a key protected by the OS secure store
                    (Windows DPAPI / macOS Keychain). A temporary <code>runtime/database.sqlite</code> exists only while Kipit is open
                    and is removed again on quit.
                </li>
                <li>
                    Builds that ship PHP with <strong>SQLCipher</strong> can unlock page-level encryption inside SQLite as well;
                    until then, sealed-at-rest + OS key storage is the shipped path. Product media and sync credentials are encrypted separately.
                    Traffic to the cloud uses HTTPS.
                </li>
                <li>
                    Access is also limited with OS permissions (restricted folder/file ACLs for the signed-in user).
                    This is not a substitute for BitLocker/FileVault.
                </li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-800 mt-10 mb-4">Integrity, sync drift, and cloud-wins repair</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li>
                    On startup and during sync, Kipit verifies an HMAC fingerprint over business tables
                    (products, stocks, sales, purchases, receivables, roles/permissions, settings, and related data).
                    Unexpected edits to the local database fail this check.
                </li>
                <li>
                    <strong>Detection is mandatory.</strong> When integrity fails (or drift repair mode is active), the desktop
                    <strong>skips pushing local changes</strong> so tampered offline rows cannot overwrite the cloud,
                    purges local commercial tables that could win last-write-wins, and <strong>force-pulls from the server</strong>
                    (cloud always overwrites those rows). Repair can resume across sync runs for large shops.
                </li>
                <li>
                    Normal online sync still uses <strong>last-write-wins</strong> for ordinary conflicts
                    (newer <code>updated_at</code> wins). There is no manual merge UI.
                    Integrity failures are the exception: cloud wins until repair finishes.
                </li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-800 mt-10 mb-4">Honest limits</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li>
                    While Kipit is open, a privileged user or malware on the same OS account can still access the unlocked runtime database.
                    Disk encryption, account hygiene, and antivirus reduce that risk; they do not eliminate it.
                </li>
                <li>
                    Kipit POS does not currently hold SOC 2 or ISO 27001 certifications.
                    We document architecture here so buyers can evaluate fit without marketing overclaims.
                </li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-800 mt-10 mb-4">Vulnerability disclosure</h2>
            <p>
                If you believe you have found a security issue, email
                <a href="mailto:security@kipitpos.com" class="text-kipit-primary hover:underline">security@kipitpos.com</a>
                with a clear description and steps to reproduce.
                Please give us a reasonable time to investigate before public disclosure.
                Do not include customer payment card data in reports.
            </p>
            <p>
                General support remains at
                <a href="mailto:support@kipitpos.com" class="text-kipit-primary hover:underline">support@kipitpos.com</a>
                or via the <a href="/contact" class="text-kipit-primary hover:underline">contact form</a>.
            </p>
        </div>
    </div>
</x-marketing-layout>
