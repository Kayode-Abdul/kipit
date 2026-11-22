<x-marketing-layout title="API Documentation">
    <div class="max-w-4xl mx-auto py-16 px-6 lg:px-8">
        <div class="text-center mb-16">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4">API Documentation</h1>
            <p class="text-lg text-gray-600">Integrate Kipit with your existing tools and services.</p>
        </div>
        
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-8">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Our public API is currently in Beta. Contact support to request early access and an API key.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="prose prose-lg text-gray-600">
            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Overview</h2>
            <p>The Kipit API is organized around REST. Our API has predictable resource-oriented URLs, accepts form-encoded request bodies, returns JSON-encoded responses, and uses standard HTTP response codes, authentication, and verbs.</p>
            
            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Authentication</h2>
            <p>Authenticate your account when using the API by including your secret API key in the request. Your API keys carry many privileges, so be sure to keep them secure.</p>
            <pre class="bg-gray-800 text-gray-100 p-4 rounded-md overflow-x-auto"><code>Authorization: Bearer YOUR_API_KEY</code></pre>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Tenant isolation</h2>
            <p>
                Kipit POS is multi-tenant. Each shop has its own database. API requests are resolved by the shop’s
                <strong>domain</strong> (hostname), which selects that shop’s database connection. Tokens issued for one
                shop cannot query another shop’s data. Call the API on your shop host (for example
                <code>https://your-shop.{{ config('tenancy.central_domains')[0] ?? 'example.com' }}</code>), not the central marketing site.
            </p>
            <p>
                More detail is on our <a href="/security" class="text-kipit-primary hover:underline">Security</a> page.
            </p>
            
            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Endpoints (Coming Soon)</h2>
            <ul>
                <li><strong>Products:</strong> Manage your inventory catalog, pricing, and stock levels.</li>
                <li><strong>Transactions:</strong> Retrieve sales data and transaction histories.</li>
                <li><strong>Customers:</strong> Manage your customer CRM data.</li>
            </ul>
        </div>
    </div>
</x-marketing-layout>
