@php
    $user = auth()->user();

    // Menú: cada opción se muestra solo si el usuario tiene el permiso
    $menu = $user->isSuperAdmin()
        ? [
            ['route' => 'super.businesses.index', 'icon' => 'bi-shop', 'label' => 'Negocios', 'can' => null],
        ]
        : [
            ['route' => 'dashboard', 'icon' => 'bi-house-door', 'label' => 'Inicio', 'can' => 'dashboard.ver'],
            ['route' => 'branches.index', 'icon' => 'bi-geo-alt', 'label' => 'Sucursales', 'can' => 'sucursales.gestionar'],
            ['route' => 'users.index', 'icon' => 'bi-people', 'label' => 'Usuarios', 'can' => 'usuarios.gestionar'],
            ['route' => 'settings.edit', 'icon' => 'bi-gear', 'label' => 'Configuración', 'can' => 'configuracion.editar'],
        ];

    $menu = array_values(array_filter($menu, fn ($m) => $m['can'] === null || $user->can($m['can'])));

    // En el celular caben 3 accesos + "Más"
    $bottom = array_slice($menu, 0, 3);
    $more = array_slice($menu, 3);

    $appName = $business?->name ?? config('app.name');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ $appName }}</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ $business?->primary_color ?? '#312E81' }}">
    <link rel="icon" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">

    <link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/app.css?v=1">
    @if ($business)
        <style>:root { --brand: {{ $business->primary_color }}; }</style>
    @endif
</head>
<body>

{{-- ===== PC: menú lateral ===== --}}
<aside class="app-sidebar d-none d-lg-flex">
    <div class="brand">
        <img src="/icons/icon-192.png" width="34" height="34" class="rounded-3" alt="">
        <div>{{ $appName }}<small>{{ $user->branch?->name ?? ($user->isSuperAdmin() ? 'Plataforma' : 'Todas las sucursales') }}</small></div>
    </div>
    <nav class="nav flex-column gap-1">
        @foreach ($menu as $m)
            <a class="nav-link @if (request()->routeIs($m['route'])) active @endif" href="{{ route($m['route']) }}">
                <i class="bi {{ $m['icon'] }}"></i> {{ $m['label'] }}
            </a>
        @endforeach
    </nav>
    <div class="user">
        <button class="btn btn-sm btn-warning w-100 mb-3 d-none" data-install><i class="bi bi-download"></i> Instalar app</button>
        <div class="d-flex align-items-center gap-2">
            <div class="avatar" style="background:rgba(255,255,255,.15);color:#fff">{{ $user->initials() }}</div>
            <div class="small lh-sm flex-grow-1">
                <div class="fw-semibold">{{ $user->name }}</div>
                <div class="opacity-75">{{ $user->roleLabel() }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn-sm btn-link text-white" title="Cerrar sesión"><i class="bi bi-box-arrow-right fs-5"></i></button>
            </form>
        </div>
    </div>
</aside>

<div class="app-content">
    {{-- ===== Celular: barra superior ===== --}}
    <header class="app-topbar d-lg-none">
        <div>
            <h1>{{ $appName }}</h1>
            <small>{{ $user->name }} · {{ $user->roleLabel() }}</small>
        </div>
        <button class="btn btn-sm btn-warning rounded-pill fw-semibold d-none" data-install><i class="bi bi-download"></i> Instalar</button>
    </header>

    <main class="app-main">
        @yield('content')
    </main>
</div>

{{-- ===== Celular: menú inferior ===== --}}
<nav class="app-bottomnav d-lg-none">
    @foreach ($bottom as $m)
        <a href="{{ route($m['route']) }}" class="@if (request()->routeIs($m['route'])) active @endif">
            <i class="bi {{ $m['icon'] }}"></i>{{ $m['label'] }}
        </a>
    @endforeach
    <button type="button" data-bs-toggle="offcanvas" data-bs-target="#more-menu">
        <i class="bi bi-three-dots"></i>Más
    </button>
</nav>

<div class="offcanvas offcanvas-bottom rounded-top-4 h-auto" tabindex="-1" id="more-menu">
    <div class="offcanvas-body pb-4">
        <div class="list-group list-group-flush">
            @foreach ($more as $m)
                <a href="{{ route($m['route']) }}" class="list-group-item list-group-item-action py-3">
                    <i class="bi {{ $m['icon'] }} me-2"></i>{{ $m['label'] }}
                </a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="list-group-item list-group-item-action py-3 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</button>
            </form>
        </div>
    </div>
</div>

<div id="toasts"></div>

<script src="/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="/js/app.js?v=1"></script>
@stack('scripts')
</body>
</html>
