<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicio suspendido · {{ $business->name }}</title>
    <link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/css/app.css?v=1">
</head>
<body>
<div class="auth-wrap">
    <div class="card auth-card p-4 p-md-5 text-center">
        <img src="/icons/icon-192.png" width="64" height="64" class="rounded-4 mb-3 mx-auto" alt="">
        <h1 class="h4 fw-bold">{{ $business->name }}</h1>
        <p class="text-secondary mb-0">El servicio de este negocio está suspendido temporalmente.<br>Comunícate con el administrador de la plataforma.</p>
    </div>
</div>
</body>
</html>
