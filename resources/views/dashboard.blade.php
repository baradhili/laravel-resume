@extends('layouts.app')

@section('title', 'Dashboard')

@section('header')
    @isset($header)
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset
@endsection

@section('content')
    <p>Main content here</p>
@endsection

@push('scripts')
    <script>
        // Page-specific JS
    </script>
@endpush