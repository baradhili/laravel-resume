<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS (Local - Development Only) -->
    <!-- ⚠️ For production: compile CSS with Tailwind CLI instead -->
    <script src="{{ asset('js/tailwind.js') }}"></script>

    <!-- Alpine.js (Local) -->
    <script defer src="{{ asset('js/alpine.js') }}"></script>

    <!-- Custom Styles Stack -->
    @stack('styles')
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        @yield('header')

        <main>
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dropdown', () => ({
            open: false,
            toggle() {
                this.open = !this.open
            },
            close() {
                this.open = false
            }
        }))
    })
</script>

</html>
