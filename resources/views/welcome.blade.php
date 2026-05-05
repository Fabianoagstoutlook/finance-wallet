<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-[#002948] text-white antialiased">
        <a href="{{ route('login') }}" class="block w-full min-h-screen no-underline text-inherit">
            <div class="min-h-screen flex items-center justify-center">
                <div class="flex flex-col items-center gap-6">
                    <x-application-logo class="w-28 h-28" />
                    <h1 class="text-3xl lg:text-4xl font-semibold">{{ config('app.name', 'Carteira Financeira') }}</h1>
                </div>
            </div>
        </a>
    </body>
</html>