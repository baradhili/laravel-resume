{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.guest') {{-- Extend the guest layout --}}

@section('content') {{-- Define the content section that fills the @yield('content') in the layout --}}

{{-- The form content goes inside the section area defined by the guest layout --}}
<form method="POST" action="{{ route('login') }}">
    @csrf {{-- Essential: Laravel's CSRF protection --}}

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
    </div>

    <div class="mt-4">
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input id="password" type="password" name="password" required
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
    </div>

    <div class="flex items-center justify-between mt-4">
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input id="remember" name="remember" type="checkbox"
                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            </div>
            <div class="ml-3 text-sm">
                <label for="remember" class="text-gray-700">Remember me</label>
            </div>
        </div>

        @if (Route::has('password.request'))
            <div class="text-sm">
                <a href="{{ route('password.request') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Forgot your password?
                </a>
            </div>
        @endif
    </div>

    <div class="mt-6">
        <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Log in
        </button>
    </div>
</form>

{{-- Optional: Display errors using Tailwind-compatible styling --}}
@if ($errors->any())
    <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
        <ul class="list-disc pl-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@endsection {{-- End the content section --}}