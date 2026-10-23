@extends('layouts.app')
@section('title', 'Inicio')

@section('content')
<div class="page-head">
    <div>
        <h2>Hola, {{ strtok(auth()->user()->name, ' ') }} 👋</h2>
        <div class="text-secondary small">{{ now()->translatedFormat('l d \d\e F, Y') }}</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3 h-100">
            <div class="icon mb-2"><i class="bi bi-geo-alt"></i></div>
            <div class="label">Sucursales activas</div>
            <div class="value">{{ $branchesCount }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3 h-100">
            <div class="icon mb-2"><i class="bi bi-people"></i></div>
            <div class="label">Usuarios activos</div>
            <div class="value">{{ $usersCount }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3 h-100">
            <div class="icon mb-2"><i class="bi bi-cash-coin"></i></div>
            <div class="label">Ventas de hoy</div>
            <div class="value">{{ $business->money(0) }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3 h-100">
            <div class="icon mb-2"><i class="bi bi-star"></i></div>
            <div class="label">{{ $business->points_label }} emitidos</div>
            <div class="value">0</div>
        </div>
    </div>
</div>

<div class="card p-4 mt-3">
    <h3 class="h6 fw-bold mb-2"><i class="bi bi-info-circle text-brand"></i> Módulo 0 listo</h3>
    <p class="text-secondary mb-0 small">
        Ya puedes gestionar sucursales, usuarios y la configuración del negocio.
        Las ventas y los {{ mb_strtolower($business->points_label) }} se llenarán cuando agreguemos los módulos de clientes y compras.
    </p>
</div>
@endsection
