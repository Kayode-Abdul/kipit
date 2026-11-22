<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('livewire.components.layouts.guest');

// Single source of truth — keep in sync with package.json / desktop builds.
$appVersion = trim((string) (file_exists(base_path('version.txt')) ? file_get_contents(base_path('version.txt')) : '0'));
if ($appVersion === '') {
    $appVersion = '0';
}
//**** update  this on production ****
//$desktopDir = base_path('../public_html/downloads/desktop');
$desktopDir = public_path('downloads/desktop');
$macFile = "kipit-{$appVersion}-mac.dmg";
$winFile = "kipit-{$appVersion}-setup.exe";
$macReady = is_file($desktopDir.DIRECTORY_SEPARATOR.$macFile);
$winReady = is_file($desktopDir.DIRECTORY_SEPARATOR.$winFile);

state([
    'appVersion' => $appVersion,
    'downloads' => [
        [
            'platform' => 'macOS',
            'version' => 'v'.$appVersion,
            'available' => $macReady,
            'description' => 'Download Kipit POS for macOS. Includes all point of sale and inventory management features.',
            'icon' => '<svg class="w-12 h-12 text-gray-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" fill="currentColor"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>',
            'links' => $macReady
                ? [['label' => 'Download for macOS', 'url' => '/downloads/desktop/'.$macFile, 'primary' => true]]
                : [['label' => 'macOS build coming soon', 'url' => '#', 'primary' => false]],
            'requirements' => 'Requires macOS 12.0 (Monterey) or later. Apple Silicon Macs can run this installer via Rosetta.',
        ],
        [
            'platform' => 'Windows',
            'version' => 'v'.$appVersion,
            'available' => $winReady,
            'description' => 'Download Kipit POS for Windows 10 and 11. Includes all point of sale and inventory management features.',
            'icon' => '<svg class="w-12 h-12 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"><path d="M0 93.7l183.6-25.3v177.4H0V93.7zm0 324.6l183.6 25.3V268.4H0v149.9zm203.8 28L448 480V268.4H203.8v177.9zm0-380.6v180.1H448V32L203.8 65.7z"/></svg>',
            'links' => $winReady
                ? [['label' => 'Download for Windows', 'url' => '/downloads/desktop/'.$winFile, 'primary' => true]]
                : [['label' => 'Windows build coming soon', 'url' => '#', 'primary' => false]],
            'requirements' => 'Requires Windows 10 or later. Single NSIS setup wizard. Visual C++ Redistributable (2015-2022) may be required.',
        ],
    ],
]);

?>

<div>
  <section class="w-full bg-white min-h-screen pb-20" data-tails-scripts="//unpkg.com/alpinejs">
    <!-- Header -->
    <header class="relative block w-full py-6 leading-10 text-center">
      <div class="w-full px-6 mx-auto leading-10 text-center lg:px-8 max-w-7xl">
        <div class="box-border flex flex-wrap items-center justify-between -mx-4 text-indigo-900">
          <div class="relative z-10 flex items-center w-auto px-4 leading-10 lg:flex-grow-0 lg:flex-shrink-0 lg:text-left">
            <a href="/" class="flex box-border font-sans text-2xl font-bold text-left text-gray-900 no-underline bg-transparent cursor-pointer focus:no-underline items-end gap-x-2">
              <img src="{{ env('APP_URL') }}/assets/logo/image.png" class="h-10"> <p>Kipit</p>
            </a>
          </div>

          <div class="relative items-center hidden px-4 mt-2 space-x-5 font-medium leading-10 md:flex md:flex-grow-0 md:flex-shrink-0 md:mt-0 md:text-right lg:flex-grow-0 lg:flex-shrink-0">
            <a href="/download" class="font-medium text-kipit-primary hover:text-orange-700">Download</a>
            <a href="/blog" class="font-medium text-gray-500 hover:text-gray-900">Blog</a>
            <a href="/pricing" class="font-medium text-gray-500 hover:text-gray-900">Pricing</a>
            <a href="/member/login"
              class="bg-gray-100 text-gray-400 md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-gray-100 focus:text-gray-700 hover:text-gray-700"
            >Login</a>
            <a href="{{ route('auth.register') }}" class="bg-kipit-primary text-white md:w-auto w-full px-8 py-3 rounded-full flex items-center justify-center font-medium text-lg focus:ring-offset-2 focus:ring-2 focus:ring-kipit-primary">Register POS</a>
          </div>

          <!-- Sidebar -->
          <div class="md:hidden">
            <div>
              <div>
                <button class="text-white hidden left-10 fixed top-20 text-2xl px-3 py-1 bg-kipit-primary rounded-lg z-50"
                  x-ref="xButton"
                  x-on:click="$refs.menu.classList.toggle('hidden'); $refs.xButton.classList.toggle('hidden');"
                  >X</button>
              </div>
              <div class="fixed inset-y-0 right-0 w-64 bg-gray-800 text-white flex-col justify-between z-40 hidden" x-ref="menu">
                <div class="flex flex-col mt-8">
                  <a href="/download" class="px-6 py-3 text-sm font-medium text-kipit-primary">Download</a>
                  <a href="/blog" class="px-6 py-3 text-sm font-medium">Blog</a>
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

    <!-- Main Content -->
    <main class="w-full relative">
      <div class="max-w-7xl px-6 lg:px-8 mx-auto py-16 lg:py-20">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl mb-6">
            Download <span class="text-kipit-primary">Kipit</span>
          </h1>
          <p class="text-xl text-gray-500">
            Choose your platform below to download the latest version of Kipit. Experience seamless point of sale and inventory management on any device.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          @foreach($downloads as $download)
            <div class="flex flex-col bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
              <div class="p-8 flex-grow">
                <div class="flex items-center justify-between mb-6">
                  {!! $download['icon'] !!}
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    {{ $download['version'] }}
                  </span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ $download['platform'] }}</h3>
                <p class="text-gray-500 mb-6 min-h-[80px]">
                  {{ $download['description'] }}
                </p>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-md mb-6">
                  <div class="flex">
                    <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm text-blue-700">
                        {{ $download['requirements'] }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="px-8 pb-8 pt-2 bg-gray-50 flex flex-col gap-y-3 mt-auto border-t border-gray-100">
                @foreach($download['links'] as $link)
                  @if(($download['available'] ?? false) && ($link['url'] ?? '#') !== '#')
                    <a href="{{ $link['url'] }}"
                       @if(str_contains($link['url'], 'github')) target="_blank" @endif
                       class="w-full flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-kipit-primary hover:bg-orange-600 focus:ring-2 focus:ring-offset-2 focus:ring-kipit-primary transition-colors duration-200">
                      {{ $link['label'] }}
                      <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </a>
                  @else
                    <span class="w-full flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-500 bg-white cursor-not-allowed">
                      {{ $link['label'] }}
                    </span>
                  @endif
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </main>

    <!-- Footer -->
    <section id="footer" class="bg-gray-800 text-white py-10 flex justify-center gap-x-3 mt-auto">
      <p>Kipit POS made with </p>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
        <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
      </svg>
    </section>
  </section>
</div>
