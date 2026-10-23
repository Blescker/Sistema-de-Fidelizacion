@extends('layouts.app')
@section('title', 'Negocios')

@section('content')
<div class="page-head">
    <h2>Negocios</h2>
    <button class="btn btn-primary d-none d-lg-inline-block"
            data-open-form="#modal-business" data-title="Nuevo negocio"
            data-action="{{ route('super.businesses.store') }}" data-method="POST"
            data-values='{"industry":"cafeteria","branch_name":"Principal"}'>
        <i class="bi bi-plus-lg"></i> Nuevo negocio
    </button>
</div>

<div class="card item-list" data-list>
    @forelse ($businesses as $b)
        <div class="item @unless ($b->isActive()) inactive @endunless">
            <div class="avatar" style="background: {{ $b->primary_color }}22; color: {{ $b->primary_color }}"><i class="bi bi-shop"></i></div>
            <div class="grow">
                <div class="title">{{ $b->name }}
                    @unless ($b->isActive()) <span class="badge text-bg-danger ms-1">Suspendido</span> @endunless
                </div>
                <div class="meta">
                    <a href="{{ $b->url() }}" target="_blank" rel="noopener" class="text-brand text-decoration-none">{{ $b->slug }}.{{ config('app.domain') }} <i class="bi bi-box-arrow-up-right small"></i></a>
                    <br class="d-md-none"><span class="d-none d-md-inline">·</span>
                    {{ $industries[$b->industry] ?? $b->industry }} · {{ $b->branches_count }} sucursal(es) · {{ $b->users_count }} usuario(s)
                </div>
            </div>
            <button class="btn btn-sm {{ $b->isActive() ? 'btn-outline-danger' : 'btn-outline-success' }}"
                    data-patch="{{ route('super.businesses.toggle', $b) }}"
                    data-confirm="{{ $b->isActive() ? '¿Suspender '.$b->name.'? Sus usuarios no podrán ingresar.' : '¿Reactivar '.$b->name.'?' }}">
                {{ $b->isActive() ? 'Suspender' : 'Activar' }}
            </button>
        </div>
    @empty
        <div class="p-4 text-center text-secondary">Aún no hay negocios.</div>
    @endforelse
</div>

<button class="btn btn-primary fab d-lg-none" aria-label="Nuevo negocio"
        data-open-form="#modal-business" data-title="Nuevo negocio"
        data-action="{{ route('super.businesses.store') }}" data-method="POST"
        data-values='{"industry":"cafeteria","branch_name":"Principal"}'>
    <i class="bi bi-plus-lg"></i>
</button>

<div class="modal fade" id="modal-business" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" data-ajax data-success="refresh" novalidate>
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold text-secondary small text-uppercase mb-3">Negocio</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre *</label>
                        <input class="form-control" name="name" placeholder="Café Miraflores" id="business-name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subdominio (su dirección) *</label>
                        <div class="input-group">
                            <input class="form-control" name="subdomain" id="business-subdomain" autocapitalize="none" autocorrect="off" spellcheck="false" placeholder="cafe-miraflores">
                            <span class="input-group-text">.{{ config('app.domain') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">RUC</label>
                        <input class="form-control" name="ruc" inputmode="numeric" maxlength="11">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rubro *</label>
                        <select class="form-select" name="industry">
                            @foreach ($industries as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sucursal principal</label>
                        <input class="form-control" name="branch_name">
                    </div>
                </div>
                <h6 class="fw-bold text-secondary small text-uppercase mb-3 mt-4">Administrador del negocio</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre *</label>
                        <input class="form-control" name="admin_name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario *</label>
                        <input class="form-control" name="admin_username" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" placeholder="ej: carlos.rios">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña *</label>
                        <input class="form-control" type="password" name="admin_password" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear negocio</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Sugiere el subdominio a partir del nombre ("Café Miraflores" → "cafe-miraflores")
    // hasta que el usuario lo edite a mano.
    (function () {
        const name = document.getElementById('business-name');
        const sub = document.getElementById('business-subdomain');
        let touched = false;

        sub.addEventListener('input', () => (touched = true));
        sub.form.addEventListener('form:filled', () => (touched = false));
        name.addEventListener('input', () => {
            if (touched) return;
            sub.value = name.value
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 30);
        });
    })();
</script>
@endpush
