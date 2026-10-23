@extends('layouts.app')
@section('title', 'Usuarios')

@section('content')
<div class="page-head">
    <h2>Usuarios</h2>
    <button class="btn btn-primary d-none d-lg-inline-block"
            data-open-form="#modal-user" data-title="Nuevo usuario"
            data-action="{{ route('users.store') }}" data-method="POST"
            data-values='{"role":"cajero"}'>
        <i class="bi bi-plus-lg"></i> Nuevo usuario
    </button>
</div>

<div class="card item-list" data-list>
    @foreach ($users as $u)
        <div class="item @unless ($u->is_active) inactive @endunless">
            <div class="avatar">{{ $u->initials() }}</div>
            <div class="grow">
                <div class="title">{{ $u->name }}
                    @if ($u->is(auth()->user())) <span class="badge text-bg-light ms-1">Tú</span> @endif
                    @unless ($u->is_active) <span class="badge text-bg-secondary ms-1">Inactivo</span> @endunless
                </div>
                <div class="meta">
                    {{ $u->roleLabel() }} · {{ $u->branch?->name ?? 'Todas las sucursales' }}
                    · <span class="font-monospace">{{ $u->username }}</span>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown" aria-label="Acciones"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item"
                            data-open-form="#modal-user" data-title="Editar usuario"
                            data-action="{{ route('users.update', $u) }}" data-method="PUT"
                            data-values="{{ json_encode($u->only('name', 'username', 'phone', 'branch_id') + ['role' => $u->roles->first()?->name]) }}">
                        <i class="bi bi-pencil me-2"></i>Editar</button></li>
                    @unless ($u->is(auth()->user()))
                        <li><button class="dropdown-item {{ $u->is_active ? 'text-danger' : 'text-success' }}"
                                data-patch="{{ route('users.toggle', $u) }}"
                                data-confirm="{{ $u->is_active ? '¿Desactivar a '.$u->name.'? Ya no podrá ingresar.' : '¿Activar a '.$u->name.'?' }}">
                            <i class="bi {{ $u->is_active ? 'bi-person-x' : 'bi-person-check' }} me-2"></i>{{ $u->is_active ? 'Desactivar' : 'Activar' }}</button></li>
                    @endunless
                </ul>
            </div>
        </div>
    @endforeach
</div>

<button class="btn btn-primary fab d-lg-none" aria-label="Nuevo usuario"
        data-open-form="#modal-user" data-title="Nuevo usuario"
        data-action="{{ route('users.store') }}" data-method="POST"
        data-values='{"role":"cajero"}'>
    <i class="bi bi-plus-lg"></i>
</button>

<div class="modal fade" id="modal-user" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" data-ajax data-success="refresh" novalidate id="form-user">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre completo *</label>
                    <input class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Usuario (para ingresar) *</label>
                    <input class="form-control" name="username" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" placeholder="ej: ana.quispe" required>
                    <div class="form-text">Minúsculas, números, punto o guion bajo. Sin espacios.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Celular</label>
                    <input class="form-control" name="phone" inputmode="tel" placeholder="987 654 321">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Rol *</label>
                        <select class="form-select" name="role" required>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Sucursal</label>
                        <select class="form-select" name="branch_id">
                            <option value="" data-all>Todas</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label">
                        Contraseña <span data-only-create>*</span>
                        <span data-only-edit class="text-secondary small">(déjala vacía para no cambiarla)</span>
                    </label>
                    <input class="form-control" type="password" name="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
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

@push('scripts')
<script>
    // El cajero trabaja en una sucursal fija: se oculta la opción "Todas"
    (function () {
        const form = document.getElementById('form-user');
        const role = form.querySelector('[name=role]');
        const branch = form.querySelector('[name=branch_id]');
        const all = branch.querySelector('[data-all]');

        function sync() {
            const cashier = role.value === 'cajero';
            all.hidden = cashier;
            if (cashier && branch.value === '') branch.selectedIndex = 1;
        }
        role.addEventListener('change', sync);
        form.addEventListener('form:filled', sync);
    })();
</script>
@endpush
