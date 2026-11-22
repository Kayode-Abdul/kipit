<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kipit is a multi-tenant Point of Sale (POS) application that simplifies inventory, sales, and store management across web and desktop.">
    <meta name="keywords" content="POS, point of sale, inventory, sales, multi-tenant, desktop, web">
    <meta name="author" content="kipit">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/logo/image.png">

    <!-- Open Graph tags for social media -->
    <meta property="og:title" content="Kipit - Point of Sale (POS) Application">
    <meta property="og:description" content="Kipit is a multi-tenant Point of Sale (POS) application that simplifies inventory, sales, and store management across web and desktop.">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:image" content="{{ env('APP_URL') }}/assets/logo/image.png">
    <meta property="og:type" content="website">

    <!-- Twitter Card tags for Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@yourtwitterhandle">
    <meta name="twitter:title" content="Kipit - Point of Sale (POS) Application">
    <meta name="twitter:description" content="Kipit is a multi-tenant Point of Sale (POS) application that simplifies inventory, sales, and store management across web and desktop.">
    <meta name="twitter:image" content="{{ env('APP_URL') }}/assets/logo/image.png">

    <title>{{ config('app.name', 'Kipit') }} - Point of Sale (POS)</title>
    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
  </head>
  <body>
    {{ $slot }}
    @livewireScripts
    @filamentScripts

  </body>
</html>
