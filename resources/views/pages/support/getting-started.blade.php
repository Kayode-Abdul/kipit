<x-marketing-layout title="Getting Started">
    <div class="max-w-4xl mx-auto py-16 px-6 lg:px-8">
        <div class="text-center mb-16">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Getting Started with Kipit</h1>
            <p class="text-lg text-gray-600">A quick step-by-step guide on how to create your store account and access your POS.</p>
        </div>
        
        <div class="space-y-12">
            <!-- Step 1 -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 transition-all hover:shadow-md">
                <div class="flex items-center gap-4 mb-6 border-b pb-4">
                    <div class="w-12 h-12 bg-kipit-primary text-white rounded-full flex items-center justify-center font-bold text-xl">1</div>
                    <h2 class="text-2xl font-bold text-gray-800">Register Your Store</h2>
                </div>
                <div class="space-y-4 text-gray-600">
                    <p class="text-lg">To begin using Kipit, you must first register your business as a tenant on our platform.</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Click the <strong>Register POS</strong> button at the top right of the navigation bar.</li>
                        <li>Fill out the registration form with your business details, email, and password.</li>
                        <li>This will create your central store account and your first location.</li>
                    </ul>
                    <div class="pt-4">
                        <a href="{{ route('auth.register') ?? '#' }}" class="inline-flex items-center gap-2 px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-kipit-primary hover:bg-orange-600 transition-colors">
                            Go to Registration
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 transition-all hover:shadow-md">
                <div class="flex items-center gap-4 mb-6 border-b pb-4">
                    <div class="w-12 h-12 bg-kipit-primary text-white rounded-full flex items-center justify-center font-bold text-xl">2</div>
                    <h2 class="text-2xl font-bold text-gray-800">Logging Into Your POS</h2>
                </div>
                <div class="space-y-4 text-gray-600">
                    <p class="text-lg">Once registered, you can log into your store's dedicated POS dashboard.</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Click the <strong>Login</strong> button at the top of the page.</li>
                        <li>Enter your registered email and password.</li>
                        <li>You will be redirected to your Filament Admin panel where you can manage products, staff, and sales.</li>
                    </ul>
                    <div class="pt-4">
                        <a href="/member/login" class="inline-flex items-center gap-2 px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Go to Login
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 transition-all hover:shadow-md">
                <div class="flex items-center gap-4 mb-6 border-b pb-4">
                    <div class="w-12 h-12 bg-kipit-primary text-white rounded-full flex items-center justify-center font-bold text-xl">3</div>
                    <h2 class="text-2xl font-bold text-gray-800">Navigating the Dashboard</h2>
                </div>
                <div class="space-y-4 text-gray-600">
                    <p class="text-lg">Your POS Dashboard is powered by Filament, making it incredibly fast and intuitive.</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Products:</strong> Navigate to the Products section to add your inventory.</li>
                        <li><strong>Sales:</strong> Use the POS interface to process transactions and print receipts.</li>
                        <li><strong>Staff:</strong> Add employees and manage their permissions securely.</li>
                    </ul>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 transition-all hover:shadow-md">
                <div class="flex items-center gap-4 mb-6 border-b pb-4">
                    <div class="w-12 h-12 bg-kipit-primary text-white rounded-full flex items-center justify-center font-bold text-xl">4</div>
                    <h2 class="text-2xl font-bold text-gray-800">Updating Kipit Later</h2>
                </div>
                <div class="space-y-4 text-gray-600">
                    <p class="text-lg">When a new desktop build is available, install it over your current copy — you do not need to uninstall first.</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>macOS:</strong> Open the new <code>.dmg</code>, drag Kipit into Applications, and choose Replace.</li>
                        <li><strong>Windows:</strong> Run the new Setup wizard into the same install folder.</li>
                        <li><strong>Your data:</strong> Products, sales, and settings stay on the computer. The first launch after an update applies any new database changes automatically.</li>
                    </ul>
                    <p>More detail is in the <a href="/help" class="text-kipit-primary hover:underline font-medium">Help Center</a> FAQ.</p>
                </div>
            </div>

            <!-- Help Call to action -->
            <div class="bg-gray-50 rounded-lg p-8 mt-12 text-center border border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Still need help?</h3>
                <p class="text-gray-600 mb-6">If you couldn't find the answer to your question, our support team is here to help.</p>
                <a href="/contact" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-kipit-primary hover:bg-orange-600 transition-colors">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</x-marketing-layout>
