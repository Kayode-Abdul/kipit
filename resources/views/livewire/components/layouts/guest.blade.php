@php
  $pageTitle = $title ?? 'Point of Sale (POS)';
  $pageDescription = $description ?? 'Kipit is a multi-tenant Point of Sale (POS) application that simplifies inventory, sales, and store management across web and desktop.';
  $canonical = url()->current();
  $logoUrl = asset('assets/logo/image.png');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="keywords" content="POS, point of sale, inventory, sales, multi-tenant, desktop, web, Kipit">
    <meta name="author" content="Kipit POS">
    <meta name="theme-color" content="#ea580c">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="icon" type="image/png" href="{{ $logoUrl }}">
    <link rel="preload" as="image" href="{{ $logoUrl }}">

    <meta property="og:title" content="{{ config('app.name', 'Kipit') }} — {{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $logoUrl }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name', 'Kipit') }} — {{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $logoUrl }}">

    <title>{{ config('app.name', 'Kipit') }} — {{ $pageTitle }}</title>
    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
  </head>
  <body class="bg-white text-gray-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-2 focus:left-2 focus:px-4 focus:py-2 focus:bg-white focus:text-gray-900 focus:ring-2 focus:ring-kipit-primary rounded-md">
      Skip to main content
    </a>
    <div id="main-content" tabindex="-1">
      {{ $slot }}
    </div>
    @livewireScripts
    @filamentScripts
  </body>
</html>
