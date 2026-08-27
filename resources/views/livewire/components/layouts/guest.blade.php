<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="kipit is a point of sale (POS) application that simplifies your business management. download for free">
    <meta name="keywords" content="POS, open-source, free, cheap">
    <meta name="author" content="kipit">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/logo/image.png">

    <!-- Open Graph tags for social media -->
    <meta property="og:title" content="kipit - Free Point of Sale (POS) Application">
    <meta property="og:description" content="kipit is a point of sale (POS) application that simplifies your business management. download for free">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:image" content="{{ env('APP_URL') }}/assets/logo/image.png">
    <meta property="og:type" content="website">

    <!-- Twitter Card tags for Twitter -->
    <meta name="twitter:card" content="kipit - Free Point of Sale (POS) Application">
    <meta name="twitter:site" content="@yourtwitterhandle">
    <meta name="twitter:title" content="kipit - Free Point of Sale (POS) Application">
    <meta name="twitter:description" content="kipit is a point of sale (POS) application that simplifies your business management. download for free">
    <meta name="twitter:image" content="{{ env('APP_URL') }}/assets/logo/image.png">

    <title>{{ config('app.name', 'Laravel') }}</title>
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


