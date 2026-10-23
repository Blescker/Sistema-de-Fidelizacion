<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ingresar · {{ $business?->name ?? config('app.name') }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ $business?->primary_color ?? '#312E81' }}">
    <link rel="icon" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/app.css?v=1">
    @if ($business)
        <style>:root { --brand: {{ $business->primary_color }}; }</style>
    @endif
</head>
<body>
<div class="auth-wrap">
    <div class="card auth-card p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="/icons/icon-192.png" width="64" height="64" class="rounded-4 mb-3" alt="">
            <h1 class="h4 fw-bold mb-1">{{ $business?->name ?? config('app.name') }}</h1>
            <p class="text-secondary mb-0">{{ $business ? 'Ingresa con tu usuario' : 'Panel de la plataforma' }}</p>
        </div>

        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" data-ajax data-success="redirect" novalidate>
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="mb-3">
                <label class="form-label" for="username">Usuario</label>
                <input class="form-control" type="text" id="username" name="username" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                <label class="form-check-label" for="remember">Mantener sesión iniciada</label>
            </div>
            <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">Ingresar</button>
        </form>
    </div>
</div>
<div id="toasts"></div>
<script src="/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="/js/app.js?v=1"></script>
</body>
</html>
