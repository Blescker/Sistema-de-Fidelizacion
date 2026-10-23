@extends('layouts.app')
@section('title', 'Sucursales')

@section('content')
<div class="page-head">
    <h2>Sucursales</h2>
    <button class="btn btn-primary d-none d-lg-inline-block"
            data-open-form="#modal-branch" data-title="Nueva sucursal"
            data-action="{{ route('branches.store') }}" data-method="POST">
        <i class="bi bi-plus-lg"></i> Nueva sucursal
    </button>
</div>

<div class="card item-list" data-list>
    @forelse ($branches as $branch)
        <div class="item @unless ($branch->is_active) inactive @endunless">
            <div class="avatar"><i class="bi bi-shop"></i></div>
            <div class="grow">
                <div class="title">{{ $branch->name }}
                    @unless ($branch->is_active) <span class="badge text-bg-secondary ms-1">Inactiva</span> @endunless
                </div>
                <div class="meta">{{ $branch->address ?: 'Sin dirección' }} · {{ $branch->users_count }} usuario(s)</div>
            </div>
            <div class="dropdown">
                <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown" aria-label="Acciones"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item"
                            data-open-form="#modal-branch" data-title="Editar sucursal"
                            data-action="{{ route('branches.update', $branch) }}" data-method="PUT"
                            data-values="{{ json_encode($branch->only('name', 'address', 'phone')) }}">
                        <i class="bi bi-pencil me-2"></i>Editar</button></li>
                    <li><button class="dropdown-item {{ $branch->is_active ? 'text-danger' : 'text-success' }}"
                            data-patch="{{ route('branches.toggle', $branch) }}"
                            data-confirm="{{ $branch->is_active ? '¿Desactivar esta sucursal?' : '¿Activar esta sucursal?' }}">
                        <i class="bi {{ $branch->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} me-2"></i>{{ $branch->is_active ? 'Desactivar' : 'Activar' }}</button></li>
                </ul>
            </div>
        </div>
    @empty
        <div class="p-4 text-center text-secondary">Aún no hay sucursales.</div>
    @endforelse
</div>

{{-- botón flotante en celular --}}
<button class="btn btn-primary fab d-lg-none" aria-label="Nueva sucursal"
        data-open-form="#modal-branch" data-title="Nueva sucursal"
        data-action="{{ route('branches.store') }}" data-method="POST">
    <i class="bi bi-plus-lg"></i>
</button>

<div class="modal fade" id="modal-branch" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" data-ajax data-success="refresh" novalidate>
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" name="name" placeholder="Ej: Sucursal Larco" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <input class="form-control" name="address" placeholder="Av. Larco 123, Miraflores">
                </div>
                <div class="mb-1">
                    <label class="form-label">Teléfono</label>
                    <input class="form-control" name="phone" inputmode="tel" placeholder="01 234 5678">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
