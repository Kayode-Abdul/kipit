<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kipit POS — Desktop Application">
    <title>Kipit POS</title>
    <link rel="shortcut icon" type="image/x-icon" href="/assets/logo/image.png">
    @livewireStyles
    @vite(['resources/css/desktop.css'])
  </head>
  <body class="desktop-app">
    {{ $slot }}
    @livewireScripts
  </body>
</html>
