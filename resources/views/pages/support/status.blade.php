<x-marketing-layout title="System Status">
    <div class="max-w-4xl mx-auto py-16 px-6 lg:px-8">
        <div class="text-center mb-16">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4">System Status</h1>
            <p class="text-lg text-gray-600">Current status of Kipit services and APIs.</p>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-12 flex items-center justify-between">
            <div class="flex items-center">
                <span class="flex h-4 w-4 relative mr-4">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
                </span>
                <h2 class="text-xl font-bold text-green-800">All Systems Operational</h2>
            </div>
            <p class="text-sm text-green-700">Updated: {{ date('M d, Y H:i:s') }}</p>
        </div>
        
        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6 flex justify-between items-center shadow-sm">
                <span class="text-lg font-medium text-gray-900">Web Application</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Operational</span>
            </div>
            
            <div class="bg-white border border-gray-200 rounded-lg p-6 flex justify-between items-center shadow-sm">
                <span class="text-lg font-medium text-gray-900">Desktop Sync Services</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Operational</span>
            </div>
            
            <div class="bg-white border border-gray-200 rounded-lg p-6 flex justify-between items-center shadow-sm">
                <span class="text-lg font-medium text-gray-900">Public API</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Operational</span>
            </div>
            
            <div class="bg-white border border-gray-200 rounded-lg p-6 flex justify-between items-center shadow-sm">
                <span class="text-lg font-medium text-gray-900">Payment Gateway</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Operational</span>
            </div>
        </div>
    </div>
</x-marketing-layout>
