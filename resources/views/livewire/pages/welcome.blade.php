<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\mount;

layout('livewire.components.layouts.guest');

mount(function () {
    if (tenant()) {
        return redirect('/member');
    }
});

$menu = [
    [
        'title' => 'Sales Analysis',
        'description' => 'This menu makes it easy for you to see the sales analysis in your store.',
        'image' => '/assets/images/dashboard.png',
    ],
    [
        'title' => 'Stock Management',
        'description' => 'This menu makes it easy for you to manage your store\'s inventory.',
        'image' => '/assets/images/stock-management.png',
    ],
    [
        'title' => 'Payment Calculator',
        'description' => 'In this payment calculator, you can calculate manually like a regular calculator, but you can also calculate automatically.',
        'image' => '/assets/images/calculator-payment.png',
    ],
];

$mainFeatures = [
    [
        'title' => 'Inventory Management',
        'description' => 'Keep track of your stock levels in real-time, get low-stock alerts, and manage product catalogs with ease.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>',
    ],
    [
        'title' => 'Sales Analytics',
        'description' => 'View daily profit reports, track transaction histories, and analyze best-selling items to make data-driven decisions.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>',
    ],
    [
        'title' => 'Multi-Tenant & Roles',
        'description' => 'Run up to two shops on free trial from one email, then unlock more with an activation key. Assign permissions to cashiers, managers, and admins.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>',
    ],
    // Mobile app feature — restore when a mobile build ships
    // [
    //     'title' => 'Cross-Platform Sync',
    //     'description' => 'Access your POS anywhere. Available as a web app, a native desktop application, and a mobile app for on-the-go management.',
    //     'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
    //           <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
    //         </svg>',
    // ],
    [
        'title' => 'Cross-Platform Sync',
        'description' => 'Access your POS anywhere. Available as a web app and native desktop applications for Windows and macOS.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h13.5A2.25 2.25 0 0121 5.25z" />
            </svg>',
    ],
    [
        'title' => 'Offline Capability',
        'description' => 'Keep your business running without interruption. Process sales, manage inventory, and print receipts even when you lose your internet connection.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
            </svg>',
    ],
    [
        'title' => 'Hardware Integration',
        'description' => 'Connect seamlessly with your existing hardware. Print receipts instantly via USB ESC/POS printers and integrate with barcode scanners.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.92-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0v-2.94a2.25 2.25 0 0 1 2.25-2.25h6a2.25 2.25 0 0 1 2.25 2.25v2.94Z" />
            </svg>',
    ],
];

state([
    'menu' => $menu,
    'mainFeatures' => $mainFeatures,
    'subscriberEmail' => '',
    'subscribed' => false,
]);

$subscribe = function () {
    $this->validate([
        'subscriberEmail' => [
            'required',
            'email',
            \Illuminate\Validation\Rule::unique(\App\Models\Subscriber::class, 'email'),
        ],
    ], [
        'subscriberEmail.unique' => 'This email is already subscribed.',
    ]);
    \App\Models\Subscriber::create([
        'email' => $this->subscriberEmail,
        'is_active' => true,
    ]);
    $this->subscribed = true;
    $this->subscriberEmail = '';
};

?>

<div>
  <section class="w-full bg-white lg:h-screen md:h-[80vh]" x-data="{ open: false }">
    <header class="relative block w-full py-6 leading-10 text-center">
      <div class="w-full px-6 mx-auto leading-10 text-center lg:px-8 max-w-7xl">
        <div class="box-border flex flex-wrap items-center justify-between -mx-4 text-gray-900">
          <div class="relative z-10 flex items-center w-auto px-4 leading-10 lg:flex-grow-0 lg:flex-shrink-0 lg:text-left">
            <a href="/" class="flex box-border font-sans text-2xl font-bold text-left text-gray-900 no-underline bg-transparent cursor-pointer items-end gap-x-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-kipit-primary rounded">
              <img src="{{ asset('assets/logo/image.png') }}" class="h-10 w-auto" width="40" height="40" alt="Kipit POS">
              <span>Kipit</span>
            </a>
          </div>

          <nav class="relative items-center hidden px-4 mt-2 space-x-5 font-medium leading-10 md:flex md:flex-grow-0 md:flex-shrink-0 md:mt-0 md:text-right" aria-label="Primary">
            <a href="/download" class="font-medium text-gray-700 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-kipit-primary rounded">Download</a>
            <a href="/blog" class="font-medium text-gray-700 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-kipit-primary rounded">Blog</a>
            <a href="/pricing" class="font-medium text-kipit-primary hover:text-orange-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-kipit-primary rounded">Pricing</a>
            <a href="/member/login"
              class="bg-gray-100 text-gray-800 md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus-visible:ring-offset-2 focus-visible:ring-2 focus-visible:ring-gray-400 hover:text-gray-900"
            >Login</a>
            <a href="{{ route('auth.register') }}" class="bg-kipit-primary text-white md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus-visible:ring-offset-2 focus-visible:ring-2 focus-visible:ring-kipit-primary">Register POS</a>
          </nav>

          <div class="md:hidden px-4">
            <button
              type="button"
              class="inline-flex items-center justify-center p-2 rounded-md text-gray-800 hover:bg-gray-100 focus-visible:ring-2 focus-visible:ring-kipit-primary"
              @click="open = !open"
              :aria-expanded="open.toString()"
              aria-controls="home-mobile-nav"
              aria-label="Toggle navigation menu"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
              </svg>
            </button>
            <div
              id="home-mobile-nav"
              x-show="open"
              x-cloak
              @keydown.escape.window="open = false"
              class="fixed inset-y-0 right-0 w-64 bg-gray-900 text-white flex flex-col z-40"
              role="dialog"
              aria-modal="true"
              aria-label="Mobile navigation"
            >
              <div class="flex justify-end p-4">
                <button type="button" class="px-3 py-1 rounded bg-kipit-primary" @click="open = false" aria-label="Close menu">Close</button>
              </div>
              <nav class="flex flex-col" aria-label="Mobile">
                <a href="/download" class="px-6 py-3 text-sm font-medium">Download</a>
                <a href="/blog" class="px-6 py-3 text-sm font-medium">Blog</a>
                <a href="/pricing" class="px-6 py-3 text-sm font-medium">Pricing</a>
                <a href="/member/login" class="px-6 py-3 text-sm font-medium">Login</a>
                <a href="{{ route('auth.register') }}" class="px-6 py-3 text-sm font-medium">Register POS</a>
              </nav>
            </div>
            <div x-show="open" x-cloak class="fixed inset-0 bg-black/40 z-30 md:hidden" @click="open = false" aria-hidden="true"></div>
          </div>
        </div>
      </div>
    </header>
    {{-- Video modal (restore with Watch Video button when a demo video is ready)
    <div x-ref="overlay" class="fixed hidden z-40 w-screen h-screen inset-0 bg-gray-900 bg-opacity-60"></div>
    <div x-ref="dialog"
      class="hidden fixed z-50 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-auto bg-white rounded-md px-8 py-6 space-y-5 drop-shadow-lg">
      <div class="w-full flex justify-end cursor-pointer" x-on:click="$refs.overlay.classList.add('hidden'); $refs.dialog.classList.add('hidden');">
        <button class="text-gray-600">X</button>
      </div>
      <iframe width="1024" height="576"
        src="https://www.youtube.com/embed/O5rYsoAZ_sk?si=kPhn1AxNqTgQR3Sf&amp;controls=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    </div>
    --}}


    <main id="home-hero" class="w-full relative" tabindex="-1">
      <div class="max-w-7xl px-10 mx-auto flex lg:flex-row flex-col py-20">
        <div class="w-full lg:w-1/2 flex lg:justify-start justify-start md:justify-center hero-title">
          <div class="lg:py-24  lg:text-left text-left md:text-center">
            <br/>
            <h1 class="mt-4 text-4xl tracking-tight font-extrabold text-gray-800 sm:mt-5 lg:text-left text-left md:text-center sm:text-6xl lg:mt-6 xl:text-7xl">
              <span class="flex items-center justify-start lg:justify-start md:justify-center w-full">kipit <span class="text-kipit-primary ml-3">POS</span></span>
            </h1>
            <p class="mt-3 text-base text-gray-600 sm:mt-5 sm:text-xl lg:text-lg  lg:text-left text-left md:text-center xl:text-xl">
              Kipit is a powerful, multi-tenant Point of Sale (POS) system designed to streamline your business.
              <br class="xl:block hidden"> Manage inventory, process sales securely, track daily profits, and sync effortlessly across all your devices.
            </p>
            <div class="mt-6 sm:mt-8">
              <div class="flex md:flex-row flex-col md:space-x-5 md:space-y-0 space-y-5 lg:justify-start justify-center">
                <a href="/download" class="bg-kipit-primary text-white md:w-auto w-full px-8 py-4 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-kipit-primary">Download Now</a>
                {{-- Watch Video (restore when a demo video is ready)
                <a href="#_"
                  class="bg-gray-800 text-white px-8 py-4 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-gray-800"
                  x-on:click="$refs.overlay.classList.remove('hidden'); $refs.dialog.classList.remove('hidden');"
                >
                  <span class="-ml-2 mr-3"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg></span>
                  <span>Watch Video</span>
                </a>
                --}}
                <a href="/getting-started"
                  class="bg-gray-800 text-white px-8 py-4 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-gray-800"
                >
                  <span>Getting Started</span>
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="w-full lg:w-1/2 lg:flex lg:justify-center lg:max-w-none max-w-md lg:mt-0 mt-20 mx-auto relative hidden hero-phone">
          <div class="w-auto sm:w-64 absolute bottom-0 transform md:bottom-auto md:top-1/2 p-8 translate-y-16 md:translate-y-24 md:ml-16 z-10 left-0 bg-white text-gray-400 rounded-xl shadow-2xl hidden md:block">
            <div class="inline-flex absolute top-0 transform -translate-y-full bg-kipit-primary left-0  space-x-1 px-4 items-center h-9 w-auto rounded-full -mt-2">

              <svg class="w-4 h-4 text-white fill-current" viewBox="0 0 534 509" xmlns="http://www.w3.org/2000/svg"><path d="m409.8 313.24 114.8-94.637c16.238-13.441 7.84-39.762-13.441-40.879l-147.84-8.96c-8.96-.56-16.801-6.161-20.16-14.56l-54.32-138.88c-7.84-19.602-35.281-19.602-43.121 0l-54.32 138.32c-3.36 8.399-11.199 14-20.16 14.56l-148.4 8.96c-21.281 1.121-29.68 27.441-13.441 40.879l114.8 94.078c6.719 5.602 10.078 15.121 7.84 23.52l-37.52 143.92c-5.04 20.16 16.8 36.398 34.719 25.199l124.88-80.078c7.84-5.04 17.359-5.04 24.64 0l125.44 80.078c17.923 11.199 39.763-5.04 34.72-25.199l-37.52-143.36c-1.68-8.398 1.12-17.359 8.402-22.961h.002Z" fill-rule="nonzero"/></svg>
              <svg class="w-4 h-4 text-white fill-current" viewBox="0 0 534 509" xmlns="http://www.w3.org/2000/svg"><path d="m409.8 313.24 114.8-94.637c16.238-13.441 7.84-39.762-13.441-40.879l-147.84-8.96c-8.96-.56-16.801-6.161-20.16-14.56l-54.32-138.88c-7.84-19.602-35.281-19.602-43.121 0l-54.32 138.32c-3.36 8.399-11.199 14-20.16 14.56l-148.4 8.96c-21.281 1.121-29.68 27.441-13.441 40.879l114.8 94.078c6.719 5.602 10.078 15.121 7.84 23.52l-37.52 143.92c-5.04 20.16 16.8 36.398 34.719 25.199l124.88-80.078c7.84-5.04 17.359-5.04 24.64 0l125.44 80.078c17.923 11.199 39.763-5.04 34.72-25.199l-37.52-143.36c-1.68-8.398 1.12-17.359 8.402-22.961h.002Z" fill-rule="nonzero"/></svg>
              <svg class="w-4 h-4 text-white fill-current" viewBox="0 0 534 509" xmlns="http://www.w3.org/2000/svg"><path d="m409.8 313.24 114.8-94.637c16.238-13.441 7.84-39.762-13.441-40.879l-147.84-8.96c-8.96-.56-16.801-6.161-20.16-14.56l-54.32-138.88c-7.84-19.602-35.281-19.602-43.121 0l-54.32 138.32c-3.36 8.399-11.199 14-20.16 14.56l-148.4 8.96c-21.281 1.121-29.68 27.441-13.441 40.879l114.8 94.078c6.719 5.602 10.078 15.121 7.84 23.52l-37.52 143.92c-5.04 20.16 16.8 36.398 34.719 25.199l124.88-80.078c7.84-5.04 17.359-5.04 24.64 0l125.44 80.078c17.923 11.199 39.763-5.04 34.72-25.199l-37.52-143.36c-1.68-8.398 1.12-17.359 8.402-22.961h.002Z" fill-rule="nonzero"/></svg>
              <svg class="w-4 h-4 text-white fill-current" viewBox="0 0 534 509" xmlns="http://www.w3.org/2000/svg"><path d="m409.8 313.24 114.8-94.637c16.238-13.441 7.84-39.762-13.441-40.879l-147.84-8.96c-8.96-.56-16.801-6.161-20.16-14.56l-54.32-138.88c-7.84-19.602-35.281-19.602-43.121 0l-54.32 138.32c-3.36 8.399-11.199 14-20.16 14.56l-148.4 8.96c-21.281 1.121-29.68 27.441-13.441 40.879l114.8 94.078c6.719 5.602 10.078 15.121 7.84 23.52l-37.52 143.92c-5.04 20.16 16.8 36.398 34.719 25.199l124.88-80.078c7.84-5.04 17.359-5.04 24.64 0l125.44 80.078c17.923 11.199 39.763-5.04 34.72-25.199l-37.52-143.36c-1.68-8.398 1.12-17.359 8.402-22.961h.002Z" fill-rule="nonzero"/></svg>
              <svg class="w-4 h-4 text-white fill-current" viewBox="0 0 534 509" xmlns="http://www.w3.org/2000/svg"><path d="m409.8 313.24 114.8-94.637c16.238-13.441 7.84-39.762-13.441-40.879l-147.84-8.96c-8.96-.56-16.801-6.161-20.16-14.56l-54.32-138.88c-7.84-19.602-35.281-19.602-43.121 0l-54.32 138.32c-3.36 8.399-11.199 14-20.16 14.56l-148.4 8.96c-21.281 1.121-29.68 27.441-13.441 40.879l114.8 94.078c6.719 5.602 10.078 15.121 7.84 23.52l-37.52 143.92c-5.04 20.16 16.8 36.398 34.719 25.199l124.88-80.078c7.84-5.04 17.359-5.04 24.64 0l125.44 80.078c17.923 11.199 39.763-5.04 34.72-25.199l-37.52-143.36c-1.68-8.398 1.12-17.359 8.402-22.961h.002Z" fill-rule="nonzero"/></svg>
            </div>
            <p class="text-gray-800 font-bold">Anonymous</p>
            <p class="mt-2">kipit makes it easy for me to manage my business, thank you kipit.</p>
          </div>
          <div class="w-full flex items-end max-w-md h-auto relative">

            <img src="{{ asset('assets/images/product-management.png') }}" class="relative h-[40rem] w-auto max-w-full object-contain lg:left-20" width="658" height="1382" alt="Kipit POS product management screen" decoding="async" fetchpriority="high">
          </div>
        </div>
      </div>
    </main>
  </section>
  <section id="about" class="bg-gray-800 text-white py-32">
    <div class="lg:max-w-3xl mx-auto text-center px-5 lg:px-0">
      <h2 class="text-4xl font-extrabold">About kipit</h2>
       <p class="text-lg mt-5 text-gray-100">Kipit is an all-in-one Point of Sale (POS) solution built for modern businesses. Available natively on Windows, macOS, and the web. it empowers you to manage product inventory, track detailed sales analytics, print receipts via USB ESC/POS, operate multiple stores seamlessly with role-based access control, and even work completely offline when you lose your internet connection. Start with a free 30-day trial to experience full features, then purchase an access key for permanent, unrestricted access. <br><br>Ready to get started? <a href="/download" class="text-orange-300 underline hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white transition-colors">Download kipit</a> for your device today.</p>
    </div>
  </section>
  <section id="product-menu" class="py-10 my-10 text-gray-400">
    <div class="xl:max-w-7xl lg:max-w-4xl mx-auto">
      <h2 class="text-4xl font-extrabold text-center text-gray-800">kipit Menu</h2>
      <div class="lg:grid lg:grid-cols-3 grid-cols-2 my-10 gap-x-5">
        @foreach ($menu as $item)
          @if ($loop->iteration % 2 == 0)
            <div class="grid grid-cols-2 lg:grid-cols-none lg:block my-10 md:my-0">
              <div class="flex gap-x-5 justify-items-center lg:mb-10 sm:my-auto ml-10 lg:ml-0">
                <div class="h-10 min-w-2 bg-kipit-primary rounded-lg" aria-hidden="true"></div>
                <div class="lg:grid lg:grid-cols-1 xl:gap-y-3 lg:gap-y-1">
                  <h3 class="text-2xl text-gray-800 font-bold">{{ $item['title'] }}</h3>
                  <p class="text-gray-600 w-4/5">{{ $item['description'] }}</p>
                </div>
              </div>
              <div class="flex justify-center">
                <img src="{{ asset(ltrim($item['image'], '/')) }}" class="w-44" width="176" height="176" alt="{{ $item['title'] }}" loading="lazy" decoding="async">
              </div>
            </div>
          @else
            <div class="grid grid-cols-2 lg:grid-cols-none lg:block">
              <div class="flex justify-center">
                <img src="{{ asset(ltrim($item['image'], '/')) }}" class="w-44" width="176" height="176" alt="{{ $item['title'] }}" loading="lazy" decoding="async">
              </div>
              <div class="flex gap-x-5 justify-items-center lg:mt-10 sm:my-auto">
                <div class="h-10 min-w-2 bg-kipit-primary rounded-lg" aria-hidden="true"></div>
                <div class="lg:grid lg:grid-cols-1 xl:gap-y-3 lg:gap-y-1">
                  <h3 class="text-2xl text-gray-800 font-bold">{{ $item['title'] }}</h3>
                  <p class="text-gray-600 w-4/5">{{ $item['description'] }}</p>
                </div>
              </div>
            </div>
          @endif
        @endforeach
      </div>
    </div>
  </section>
  <section id="main-feature" class="bg-gray-800 text-white py-32">
    <div class="md:max-w-4xl max-w-1xl mx-auto">
      <div class="mb-20 grid grid-cols-1 gap-y-5">
        <h2 class="text-4xl font-extrabold text-center">kipit Features</h2>
        <p class="text-lg text-center sm:mx-0 mx-5 text-gray-200">With kipit, you can easily manage your store. Here are the features kipit offers.</p>
      </div>
      <div class="grid sm:grid-cols-2 lg:gap-20 gap-10 px-10">
        @foreach($mainFeatures as $feature)
        <div class="flex gap-x-4">
          <div class="bg-kipit-primary rounded-2xl flex justify-center items-center min-w-10 h-10" aria-hidden="true">
            {!! $feature['icon'] !!}
          </div>
          <div>
            <h3 class="text-2xl font-bold">{{ $feature['title'] }}</h3>
            <p class="text-gray-300 lg:w-4/5">{{ $feature['description'] }}</p>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  <section id="cta" class="xl:max-w-7xl lg:max-w-3xl md:px-10 mx-auto text-white">
    <div class="bg-kipit-primary text-center rounded-2xl my-20 p-10">
      <h2 class="font-extrabold text-3xl">Get notified when we have updates</h2>
      <p class="mt-5">Don't miss our latest updates, register your email now.</p>
      <form wire:submit="subscribe" class="mt-5 sm:mx-auto sm:flex sm:max-w-lg">
        <div class="min-w-0 flex-1">
          <label for="cta-email" class="sr-only">Email address</label>
          <input id="cta-email" type="email" wire:model="subscriberEmail" autocomplete="email" required class="block w-full rounded-md border border-transparent px-5 py-3 text-base text-gray-900 placeholder-gray-500 shadow-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-kipit-primary" placeholder="Enter your email">
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-3">
          <button type="submit" class="block w-full rounded-md border border-transparent bg-gray-900 px-5 py-3 text-base font-medium text-white shadow hover:bg-black focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-kipit-primary sm:px-10">
            <span wire:loading.remove wire:target="subscribe">Notify me</span>
            <span wire:loading wire:target="subscribe">Saving...</span>
          </button>
        </div>
      </form>
      @error('subscriberEmail') <span class="text-red-200 text-sm mt-2 block">{{ $message }}</span> @enderror
      @if($subscribed) <span class="text-green-200 text-sm mt-2 block font-bold">Successfully subscribed! We will notify you of any updates.</span> @endif
    </div>
  </section>
  <footer class="bg-gray-900 border-t border-gray-800 text-gray-300 py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12 lg:gap-8">
        <!-- Brand & Description -->
        <div class="md:col-span-1">
          <a href="/" class="flex items-center gap-x-2 text-white font-bold text-2xl mb-4">
            <img src="{{ asset('assets/logo/image.png') }}" class="h-8 w-auto" width="32" height="32" alt="Kipit POS" loading="lazy" decoding="async"> Kipit POS
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
            <li><a href="#main-feature" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
            <li><a href="{{ route('auth.register') }}" class="text-gray-400 hover:text-white transition-colors">Register POS</a></li>
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

  <!-- Floating Sticky Note -->
  <a href="/pricing" class="fixed bottom-6 right-6 bg-amber-300 hover:bg-amber-400 text-amber-900 shadow-2xl rounded-sm p-4 w-56 transform rotate-3 hover:rotate-0 transition-all z-50 border-t-8 border-amber-400 group cursor-pointer block before:absolute before:-bottom-1 before:right-0 before:w-full before:h-1 before:bg-black before:opacity-10 before:rounded-full">
    <div class="absolute -top-3 -right-3">
        <span class="flex h-6 w-6 relative">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-6 w-6 bg-red-500 text-white text-xs items-center justify-center font-bold">!</span>
        </span>
    </div>
    <p class="font-extrabold text-lg leading-tight mb-2">Need an Access Key?</p>
    <p class="text-sm font-medium leading-snug">Click here to get your activation key and unlock Kipit.</p>
  </a>

</div>
