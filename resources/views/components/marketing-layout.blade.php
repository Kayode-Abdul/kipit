<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Kipit') }} - {{ $title ?? 'POS System' }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="/assets/logo/image.png">
    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
  </head>
  <body class="bg-gray-50 flex flex-col min-h-screen">
    <header class="relative block w-full py-6 leading-10 text-center bg-white border-b border-gray-100" data-tails-scripts="//unpkg.com/alpinejs">
      <div class="w-full px-6 mx-auto leading-10 text-center lg:px-8 max-w-7xl">
        <div class="box-border flex flex-wrap items-center justify-between -mx-4 text-indigo-900">
          <div class="relative z-10 flex items-center w-auto px-4 leading-10 lg:flex-grow-0 lg:flex-shrink-0 lg:text-left">
            <a href="/" class="flex box-border font-sans text-2xl font-bold text-left text-gray-900 no-underline bg-transparent cursor-pointer focus:no-underline items-end gap-x-2">
              <img src="{{ env('APP_URL') }}/assets/logo/image.png" class="h-10"> <p>Kipit</p>
            </a>
          </div>

          <div class="relative items-center hidden px-4 mt-2 space-x-5 font-medium leading-10 md:flex md:flex-grow-0 md:flex-shrink-0 md:mt-0 md:text-right lg:flex-grow-0 lg:flex-shrink-0">
            <a href="/download" class="font-medium text-gray-500 hover:text-gray-900">Download</a>
            <a href="/blog" class="font-medium text-gray-500 hover:text-gray-900">Blog</a>
            <a href="/getting-started" class="font-medium text-gray-500 hover:text-gray-900">Getting Started</a>
            <a href="/pricing" class="font-medium text-kipit-primary hover:text-gray-900">Pricing</a>
            <a href="/member/login"
              class="bg-gray-100 text-gray-400 md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-gray-100 focus:text-gray-700 hover:text-gray-700"
            >Login</a>
            <a href="{{ route('auth.register') ?? '#' }}" class="bg-kipit-primary text-white md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-kipit-primary">Register POS</a>
          </div>

          <!-- Sidebar -->
          <div class="md:hidden">
            <div x-data="{ open: false }">
              <button class="text-white hidden left-10 fixed top-20 text-2xl px-3 py-1 bg-kipit-primary rounded-lg"
                x-ref="xButton"
                x-on:click="open = !open"
                >X</button>
              <div class="fixed inset-y-0 right-0 w-64 bg-gray-800 text-white flex-col justify-between z-20 hidden" x-show="open">
                <!-- Links -->
                <div class="flex flex-col mt-8">
                  <a href="/download" class="px-6 py-3 text-sm font-medium">Download</a>
                  <a href="/blog" class="px-6 py-3 text-sm font-medium">Blog</a>
                  <a href="/getting-started" class="px-6 py-3 text-sm font-medium">Getting Started</a>
                  <a href="/pricing" class="px-6 py-3 text-sm font-medium">Pricing</a>
                  <a href="/member/login" class="px-6 py-3 text-sm font-medium">Login</a>
                  <a href="{{ route('auth.register') ?? '#' }}" class="px-6 py-3 text-sm font-medium">Register POS</a>
                </div>
              </div>
            </div>
            <!-- Content -->
            <div class="flex items-center justify-center h-full mr-5 text-gray-800" x-data="{ open: false }" x-on:click="open = !open">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow">
      {{ $slot }}
    </main>

    <footer class="bg-gray-900 border-t border-gray-800 text-gray-300 py-12 lg:py-16">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 lg:gap-8">
          <!-- Brand & Description -->
          <div class="md:col-span-1">
            <a href="/" class="flex items-center gap-x-2 text-white font-bold text-2xl mb-4">
              <img src="{{ env('APP_URL') }}/assets/logo/image.png" class="h-8" alt="Kipit POS Logo"> Kipit POS
            </a>
            <p class="text-sm text-gray-400 mb-6">
              The ultimate multi-tenant POS solution designed to streamline your business, manage inventory, and track sales everywhere you go.
            </p>
            <div class="flex space-x-4">
              <a href="https://www.facebook.com/kipit_pos" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors">
                <span class="sr-only">Facebook</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/>
                </svg>
              </a>
              <a href="https://www.instagram.com/kipit_pos" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors">
                <span class="sr-only">Instagram</span>
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"/>
                </svg>
              </a>
            </div>
          </div>
          
          <!-- Product Links -->
          <div class="md:col-span-1 md:ml-auto">
            <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-4">Product</h3>
            <ul class="space-y-3">
              <li><a href="/download" class="text-gray-400 hover:text-white transition-colors">Download</a></li>
              <li><a href="/pricing" class="text-gray-400 hover:text-white transition-colors">Pricing</a></li>
              <li><a href="/#main-feature" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
              <li><a href="{{ route('auth.register') ?? '#' }}" class="text-gray-400 hover:text-white transition-colors">Register POS</a></li>
            </ul>
          </div>
    
          <!-- Support Links -->
          <div class="md:col-span-1 md:ml-auto">
            <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-4">Support</h3>
            <ul class="space-y-3">
              <li><a href="/getting-started" class="text-gray-400 hover:text-white transition-colors">Getting Started</a></li>
              <li><a href="/blog" class="text-gray-400 hover:text-white transition-colors">Blog</a></li>
              <li><a href="/help" class="text-gray-400 hover:text-white transition-colors">Help Center</a></li>
              <li><a href="/api-docs" class="text-gray-400 hover:text-white transition-colors">API Documentation</a></li>
              <li><a href="/contact" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
              <li><a href="/status" class="text-gray-400 hover:text-white transition-colors">System Status</a></li>
            </ul>
          </div>
    
          <!-- Legal Links -->
          <div class="md:col-span-1 md:ml-auto">
            <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-4">Legal</h3>
            <ul class="space-y-3">
              <li><a href="/privacy" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
              <li><a href="/terms" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
              <li><a href="/cookies" class="text-gray-400 hover:text-white transition-colors">Cookie Policy</a></li>
              <li><a href="/security" class="text-gray-400 hover:text-white transition-colors">Security</a></li>
            </ul>
          </div>
        </div>
        
        <div class="mt-12 pt-8 border-t border-gray-800 flex flex-col items-center gap-4 md:gap-8">
          <p class="text-sm text-gray-400 text-center">
            &copy; {{ date('Y') }} Kipit POS. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
    @livewireScripts
    @filamentScripts
  </body>
</html>