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
        'description' => 'Manage multiple storefronts from a single account and assign specific permissions to cashiers, managers, and admins.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>',
    ],
    [
        'title' => 'Cross-Platform Sync',
        'description' => 'Access your POS anywhere. Available as a web app, a native desktop application, and a mobile app for on-the-go management.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
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
]);

?>

<div>
  <section class="w-full bg-white lg:h-screen md:h-[80vh]" data-tails-scripts="//unpkg.com/alpinejs">
    <header class="relative block w-full py-6 leading-10 text-center">
      <div class="w-full px-6 mx-auto leading-10 text-center lg:px-8 max-w-7xl">
        <div class="box-border flex flex-wrap items-center justify-between -mx-4 text-indigo-900">
          <div class="relative z-10 flex items-center w-auto px-4 leading-10 lg:flex-grow-0 lg:flex-shrink-0 lg:text-left">
            <a href="/" class="flex box-border font-sans text-2xl font-bold text-left text-gray-900 no-underline bg-transparent cursor-pointer focus:no-underline items-end gap-x-2">
              <img src="{{ env('APP_URL') }}/assets/logo/image.png" class="h-10"> <p>Kipit</p>
            </a>
          </div>

          <div class="relative items-center hidden px-4 mt-2 space-x-5 font-medium leading-10 md:flex md:flex-grow-0 md:flex-shrink-0 md:mt-0 md:text-right lg:flex-grow-0 lg:flex-shrink-0">
            <a href="/download" class="font-medium text-gray-500 hover:text-gray-900">Download</a>
            <a href="/pricing" class="font-medium text-kipit-primary hover:text-gray-900">Pricing</a>
            <a href="/member/login"
              class="bg-gray-100 text-gray-400 md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-gray-100 focus:text-gray-700 hover:text-gray-700"
            >Login</a>
            <a href="{{ route('auth.register') }}" class="bg-kipit-primary text-white md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-kipit-primary">Register POS</a>
          </div>

          <!-- Sidebar -->
          <div class="md:hidden">
            <div>
              <div>
                <button class="text-white hidden left-10 fixed top-20 text-2xl px-3 py-1 bg-kipit-primary rounded-lg"
                  x-ref="xButton"
                  x-on:click="$refs.menu.classList.toggle('hidden'); $refs.xButton.classList.toggle('hidden');"
                  >X</button>
              </div>
              <div class="fixed inset-y-0 right-0 w-64 bg-gray-800 text-white flex-col justify-between z-20 hidden" x-ref="menu">
                <!-- Links -->
                <div class="flex flex-col mt-8">
                  <a href="/download" class="px-6 py-3 text-sm font-medium">Download</a>
                  <a href="/pricing" class="px-6 py-3 text-sm font-medium">Pricing</a>
                  <a href="/member/login" class="px-6 py-3 text-sm font-medium">Login</a>
                  <a href="{{ route('auth.register') }}" class="px-6 py-3 text-sm font-medium">Register POS</a>
                </div>
              </div>
            </div>
            <!-- Content -->
            <div class="flex items-center justify-center h-full mr-5 text-gray-800" x-on:click="$refs.menu.classList.toggle('hidden'); $refs.xButton.classList.toggle('hidden');">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
            </div>
          </div>
        </div>
      </div>
    </header>
    <div x-ref="overlay" class="fixed hidden z-40 w-screen h-screen inset-0 bg-gray-900 bg-opacity-60"></div>
    <!-- The dialog -->
    <div x-ref="dialog"
      class="hidden fixed z-50 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-auto bg-white rounded-md px-8 py-6 space-y-5 drop-shadow-lg">
      <!--- right Close button -->
      <div class="w-full flex justify-end cursor-pointer" x-on:click="$refs.overlay.classList.add('hidden'); $refs.dialog.classList.add('hidden');">
        <button class="text-gray-600">X</button>
      </div>
      <iframe width="1024" height="576"
        src="https://www.youtube.com/embed/O5rYsoAZ_sk?si=kPhn1AxNqTgQR3Sf&amp;controls=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    </div>


    <main class="w-full relative">
      <div class="max-w-7xl px-10 mx-auto flex lg:flex-row flex-col py-20">
        <div class="w-full lg:w-1/2 flex lg:justify-start justify-start md:justify-center hero-title">
          <div class="lg:py-24  lg:text-left text-left md:text-center">
            <br/>
            <h1 class="mt-4 text-4xl tracking-tight font-extrabold text-gray-800 sm:mt-5 lg:text-left text-left md:text-center sm:text-6xl lg:mt-6 xl:text-7xl">
              <span class="flex items-center justify-start lg:justify-start md:justify-center w-full">kipit <span class="text-kipit-primary ml-3">POS</span></span>
            </h1>
            <p class="mt-3 text-base text-gray-400 sm:mt-5 sm:text-xl lg:text-lg  lg:text-left text-left md:text-center xl:text-xl">
              Kipit is a powerful, multi-tenant Point of Sale (POS) system designed to streamline your business. 
              <br class="xl:block hidden"> Manage inventory, process sales securely, track daily profits, and sync effortlessly across all your devices.
            </p>
            <div class="mt-6 sm:mt-8">
              <div class="flex md:flex-row flex-col md:space-x-5 md:space-y-0 space-y-5 lg:justify-start justify-center">
                <a href="/download" class="bg-kipit-primary text-white md:w-auto w-full px-8 py-4 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-kipit-primary">Download Now</a>
                <a href="#_"
                  class="bg-gray-800 text-white px-8 py-4 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-gray-800"
                  x-on:click="$refs.overlay.classList.remove('hidden'); $refs.dialog.classList.remove('hidden');"
                >
                  <span class="-ml-2 mr-3"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg></span>
                  <span>Watch Video</span>
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

            <img src="/assets/images/product-management.jpeg" class="relative h-[40rem] lg:left-20">
          </div>
        </div>
      </div>
    </main>
  </section>
  <section id="about" class="bg-gray-800 text-white py-32">
    <div class="lg:max-w-3xl mx-auto text-center px-5 lg:px-0">
      <p class="text-4xl font-extrabold">About kipit</p>
      <p class="text-lg mt-5">Kipit is an all-in-one Point of Sale (POS) solution built for modern businesses. Available natively on Windows, macOS, Android, and the web, it empowers you to manage product inventory, track detailed sales analytics, print receipts via USB ESC/POS, operate multiple stores seamlessly with role-based access control, and even work completely offline when you lose your internet connection. Start with a free 30-day trial to experience full features, then purchase an access key for permanent, unrestricted access. <br><br>Ready to get started? <a href="/download" class="text-kipit-primary underline hover:text-white transition-colors">Download kipit</a> for your device today.</p>
    </div>
  </section>
  <section id="product-menu" class="py-10 my-10 text-gray-400">
    <div class="xl:max-w-7xl lg:max-w-4xl mx-auto">
      <p class="text-4xl font-extrabold text-center text-gray-800">kipit Menu</p>
      <div class="lg:grid lg:grid-cols-3 grid-cols-2 my-10 gap-x-5">
        @foreach ($menu as $item)
          @if ($loop->iteration % 2 == 0)
            <div class="grid grid-cols-2 lg:grid-cols-none lg:block my-10 md:my-0">
              <div class="flex gap-x-5 justify-items-center lg:mb-10 sm:my-auto ml-10 lg:ml-0">
                <div class="h-10 min-w-2 bg-kipit-primary rounded-lg"></div>
                <div class="lg:grid lg:grid-cols-1 xl:gap-y-3 lg:gap-y-1">
                  <p class="text-2xl text-gray-800 font-bold">{{ $item['title'] }}</p>
                  <p class="text-gray-400 w-4/5">{{ $item['description'] }}</p>
                </div>
              </div>
              <div class="flex justify-center">
                <img src="{{ $item['image'] }}" class="w-44">
              </div>
            </div>
          @else
            <div class="grid grid-cols-2 lg:grid-cols-none lg:block">
              <div class="flex justify-center">
                <img src="{{ $item['image'] }}" class="w-44">
              </div>
              <div class="flex gap-x-5 justify-items-center lg:mt-10 sm:my-auto">
                <div class="h-10 min-w-2 bg-kipit-primary rounded-lg"></div>
                <div class="lg:grid lg:grid-cols-1 xl:gap-y-3 lg:gap-y-1">
                  <p class="text-2xl text-gray-800 font-bold">{{ $item['title'] }}</p>
                  <p class="text-gray-400 w-4/5">{{ $item['description'] }}</p>
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
        <p class="text-4xl font-extrabold text-center">kipit Features</p>
        <p class="text-lg text-center sm:mx-0 mx-5">With kipit, you can easily manage your store. Here are the features kipit offers.</p>
      </div>
      <div class="grid sm:grid-cols-2 lg:gap-20 gap-10 px-10">
        @foreach($mainFeatures as $feature)
        <div class="flex gap-x-4">
          <div class="bg-kipit-primary rounded-2xl flex justify-center items-center min-w-10 h-10">
            {!! $feature['icon'] !!}
          </div>
          <div>
            <p class="text-2xl font-bold">{{ $feature['title'] }}</p>
            <p class="text-gray-400 lg:w-4/5">{{ $feature['description'] }}</p>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  <section id="cta" class="xl:max-w-7xl lg:max-w-3xl md:px-10 mx-auto text-white">
    <div class="bg-kipit-primary text-center rounded-2xl my-20 p-10">
      <p class="font-extrabold text-3xl">Get notified when we have updates</p>
      <p class="mt-5">Don't miss our latest updates, register your email now.</p>
      <form action="#" class="mt-5 sm:mx-auto sm:flex sm:max-w-lg">
        <div class="min-w-0 flex-1">
          <label for="cta-email" class="sr-only">Email address</label>
          <input id="cta-email" type="email" class="block w-full rounded-md border border-transparent px-5 py-3 text-base text-gray-900 placeholder-gray-500 shadow-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-kipit-primary" placeholder="Enter your email">
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-3">
          <button type="submit" class="block w-full rounded-md border border-transparent bg-orange-600 px-5 py-3 text-base font-medium text-white shadow hover:bg-kipit-primary focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-kipit-primary sm:px-10">Notify me</button>
        </div>
      </form>
    </div>
  </section>
  <section id="footer" class="bg-gray-800 text-white py-10 flex justify-center gap-x-3">
    <p>kipit made with </p>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
      <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
    </svg>
  </section>

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
