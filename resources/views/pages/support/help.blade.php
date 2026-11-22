<x-marketing-layout title="FAQ & Help Center">
    <div class="max-w-4xl mx-auto py-16 px-6 lg:px-8">
        <div class="text-center mb-16">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Frequently Asked Questions</h1>
            <p class="text-lg text-gray-600">Everything you need to know about setting up and using Kipit POS.</p>
        </div>
        
        <div class="space-y-8" x-data="{ active: null }">
            
            <!-- Category 1: General -->
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">General & Onboarding</h2>
                
                <div class="space-y-4">
                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 1) ? null : 1" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>How do I install the desktop app?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 1}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 1" x-collapse class="px-6 pb-4 text-gray-600">
                            You can download the desktop application for macOS or Windows from our <a href="/download" class="text-kipit-primary hover:underline font-medium">Download page</a>. Simply run the installer and follow the on-screen instructions. The first launch may take a moment as it sets up your local database.
                        </div>
                    </div>

                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 10) ? null : 10" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>How do I update to a newer desktop version?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 10}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 10" x-collapse class="px-6 pb-4 text-gray-600 space-y-3">
                            <p>Download the latest installer from the <a href="/download" class="text-kipit-primary hover:underline font-medium">Download page</a>, quit Kipit completely, then install over your current copy:</p>
                            <ul class="list-disc pl-5 space-y-1">
                                <li><strong>macOS:</strong> Open the new <code>.dmg</code> and drag Kipit into Applications. Choose Replace when asked.</li>
                                <li><strong>Windows:</strong> Run the new Setup wizard and install to the same location. You do not need to uninstall first.</li>
                            </ul>
                            <p>Your shop data stays on the computer. On first launch after updating, Kipit applies any new database changes automatically (new tables/columns) without wiping products or sales.</p>
                        </div>
                    </div>

                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 2) ? null : 2" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>How do I register a new store?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 2}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 2" x-collapse class="px-6 pb-4 text-gray-600">
                            Click the "Register POS" button in the navigation bar to create a new central account. From there, you can create and manage multiple store locations (tenants) under one unified dashboard.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category 2: Hardware -->
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2 mt-10">Hardware Integration</h2>
                
                <div class="space-y-4">
                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 3) ? null : 3" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>Are USB ESC/POS printers supported?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 3}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 3" x-collapse class="px-6 pb-4 text-gray-600">
                            Yes! Kipit supports direct ESC/POS printing via USB. You can configure your receipt printer directly within the POS interface settings without needing third-party drivers.
                        </div>
                    </div>

                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 4) ? null : 4" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>Can I use a barcode scanner?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 4}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 4" x-collapse class="px-6 pb-4 text-gray-600">
                            Absolutely. Any standard USB or Bluetooth barcode scanner that acts as a keyboard emulator will work out of the box with Kipit. Just scan the item, and it adds directly to the cart.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category 3: Data & Offline -->
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2 mt-10">Data & Offline Support</h2>
                
                <div class="space-y-4">
                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 5) ? null : 5" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>What happens if my store loses internet connection?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 5}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 5" x-collapse class="px-6 pb-4 text-gray-600">
                            Because Kipit operates as a native application with a local database, you can continue to ring up sales and print receipts offline. Once internet is restored, the desktop app syncs changes with the cloud. If the same record was edited in two places, the newer change wins (last-write-wins).
                        </div>
                    </div>

                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 6) ? null : 6" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>Is my data securely backed up?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 6}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 6" x-collapse class="px-6 pb-4 text-gray-600">
                            When online, your shop data syncs to the cloud. If a computer fails, you can install Kipit on a new machine, sign in, and pull down the synced store data. On desktop, the offline database is sealed when the app is closed (AES-256-GCM + OS secure storage). Enable BitLocker/FileVault, lock the OS account, and do not manually edit AppData. Details: <a href="/security" class="text-kipit-primary hover:underline font-medium">Security</a>.
                        </div>
                    </div>

                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 7) ? null : 7" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>What if a POS device is lost or stolen?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 7}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 7" x-collapse class="px-6 pb-4 text-gray-600">
                            In Kipit POS (web), go to <strong>Settings → Connected Devices</strong>, revoke the lost device (or all desktop devices), and change that user’s password. Wipe or recover the hardware when possible. More detail: <a href="/security" class="text-kipit-primary hover:underline font-medium">Security</a>.
                        </div>
                    </div>

                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 8) ? null : 8" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>What security steps must I take on each POS computer?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 8}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 8" x-collapse class="px-6 pb-4 text-gray-600">
                            <ol class="list-decimal pl-5 space-y-2">
                                <li>Enable <strong>BitLocker</strong> (Windows) or <strong>FileVault</strong> (Mac) on the POS drive.</li>
                                <li>Use a strong OS login password and lock the screen when unattended.</li>
                                <li>Do not copy or edit Kipit’s AppData / application-data folders.</li>
                                <li>Connect to the internet regularly so sync and integrity repair can run.</li>
                                <li>Revoke lost devices under Settings → Connected Devices and change passwords.</li>
                            </ol>
                            Full list: <a href="/security" class="text-kipit-primary hover:underline font-medium">Security</a>.
                        </div>
                    </div>

                    <!-- FAQ Item -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <button @click="active = (active === 9) ? null : 9" class="w-full text-left px-6 py-4 font-semibold text-gray-900 flex justify-between items-center focus:outline-none hover:bg-gray-50 transition-colors">
                            <span>What happens if local desktop data is tampered with?</span>
                            <svg class="h-5 w-5 text-gray-500 transform transition-transform duration-200" :class="{'rotate-180': active === 9}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="active === 9" x-collapse class="px-6 pb-4 text-gray-600">
                            Kipit checks an integrity fingerprint over sales, inventory, and related tables. If the check fails, the app skips pushing those local changes and runs a mandatory <strong>cloud-wins repair</strong>: commercial data is rebuilt from the server so tampered offline rows cannot overwrite the cloud. Keep the machine online after an integrity warning so repair can finish. Before installing a desktop update, sync online first — upgrades refresh the fingerprint automatically and will not purge data just because the schema changed. Details: <a href="/security" class="text-kipit-primary hover:underline font-medium">Security</a>.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-8 mt-12 text-center border border-gray-100">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Still need help?</h3>
                <p class="text-gray-600 mb-6">If you couldn't find the answer to your question, our support team is here to help.</p>
                <a href="/contact" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-kipit-primary hover:bg-orange-600">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</x-marketing-layout>
