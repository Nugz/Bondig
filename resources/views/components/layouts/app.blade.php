<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="bondig">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bondig') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">
    <!-- Navigation -->
    <div class="navbar bg-base-100 shadow-sm">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-10 mt-3 w-52 p-2 shadow">
                    <li>
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('upload') }}" class="{{ request()->routeIs('upload') ? 'active' : '' }}">
                            Upload
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('products') }}" class="{{ request()->routeIs('products') ? 'active' : '' }}">
                            Products
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost text-xl text-primary font-bold">
                Bondig
            </a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <li>
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary' : '' }}">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('upload') }}" class="{{ request()->routeIs('upload') ? 'bg-primary/10 text-primary' : '' }}">
                        Upload
                    </a>
                </li>
                <li>
                    <a href="{{ route('products') }}" class="{{ request()->routeIs('products') ? 'bg-primary/10 text-primary' : '' }}">
                        Products
                    </a>
                </li>
            </ul>
        </div>
        <div class="navbar-end">
            <!-- Empty for now -->
        </div>
    </div>

    <!-- Flash Messages -->
    <x-flash-messages />

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6">
        {{ $slot }}
    </main>
</body>
</html>
