{{-- resources/views/layouts/guest.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles (Basic Inline Styles to Replace Tailwind) -->
        <style>
            body {
                font-family: 'Figtree', sans-serif; /* Apply the Figtree font */
                color: #1e293b; /* Approximation of Tailwind's text-gray-900 */
                background-color: #f3f4f6; /* Approximation of Tailwind's bg-gray-100 */
                -webkit-font-smoothing: antialiased; /* Approximation of Tailwind's antialiased */
                -moz-osx-font-smoothing: grayscale;
            }

            .min-h-screen {
                min-height: 100vh;
            }

            .flex {
                display: flex;
            }

            .flex-col {
                flex-direction: column;
            }

            .items-center {
                align-items: center;
            }

            .sm\:justify-center {
                /* For small screens and up, justify content center */
                /* Using a media query to approximate sm: from Tailwind */
                /* Standard Tailwind sm breakpoint is 640px */
            }
            @media (min-width: 640px) {
                .sm\:justify-center {
                     justify-content: center;
                }
            }

            .pt-6 {
                padding-top: 1.5rem; /* Approximation of Tailwind spacing */
            }

            .sm\:pt-0 {
                 /* For small screens and up, padding top 0 */
            }
            @media (min-width: 640px) {
                .sm\:pt-0 {
                    padding-top: 0;
                }
            }

            .w-full {
                width: 100%;
            }

            .sm\:max-w-md {
                 /* For small screens and up, max width medium */
                 /* Approximation: Tailwind md is often 28rem (448px) */
            }
            @media (min-width: 640px) {
                .sm\:max-w-md {
                    max-width: 448px; /* Or use rem/em units if preferred */
                }
            }

            .mt-6 {
                margin-top: 1.5rem; /* Approximation of Tailwind spacing */
            }

            .px-6 {
                padding-left: 1.5rem; /* Approximation of Tailwind spacing */
                padding-right: 1.5rem; /* Approximation of Tailwind spacing */
            }

            .py-4 {
                padding-top: 1rem; /* Approximation of Tailwind spacing */
                padding-bottom: 1rem; /* Approximation of Tailwind spacing */
            }

            .bg-white {
                background-color: #ffffff; /* Approximation of Tailwind's bg-white */
            }

            .shadow-md {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* Approximation of Tailwind's shadow-md */
            }

            .overflow-hidden {
                overflow: hidden;
            }

            .sm\:rounded-lg {
                 /* For small screens and up, large rounded corners */
            }
            @media (min-width: 640px) {
                .sm\:rounded-lg {
                    border-radius: 0.5rem; /* Approximation of Tailwind's rounded-lg */
                }
            }

            /* Basic style for the logo container */
            a > svg {
                 /* Ensure the SVG logo scales nicely */
                 width: 5rem; /* Approximation of Tailwind's w-20 (20 * 0.25rem = 5rem) */
                 height: 5rem; /* Approximation of Tailwind's h-20 */
            }

            /* Ensure the content area fills available space nicely */
            .content-container {
                 width: 100%;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    {{--<x-application-logo class="w-20 h-20 fill-current text-gray-500" />--}}
                    <!-- Placeholder for Logo: Replace with your actual logo SVG or image tag -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="fill-current text-gray-500">
                         <path d="M11.644 1.59a.75.75 0 01.712 0l9.75 5.25a.75.75 0 010 1.32l-9.75 5.25a.75.75 0 01-.712 0l-9.75-5.25a.75.75 0 010-1.32l9.75-5.25z"></path>
                         <path d="M3.265 10.602l7.644 4.113a2.25 2.25 0 002.178 0l7.644-4.113a.75.75 0 011.146.666v9.75a.75.75 0 01-.666 1.146l-9.572 3.191a2.25 2.25 0 01-1.412 0l-9.572-3.191a.75.75 0 01-.666-1.146v-9.75a.75.75 0 011.146-.666z"></path>
                    </svg>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                 <div class="content-container">
                    @yield('content') {{-- Define the yield section --}}
                 </div>
            </div>
        </div>
    </body>
</html>