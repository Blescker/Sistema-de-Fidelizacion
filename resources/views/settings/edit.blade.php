@extends('layouts.app')
@section('title', 'Configuración')

@section('content')
<div class="page-head">
    <h2>Configuración del negocio</h2>
</div>

<form class="card p-4" method="POST" action="{{ route('settings.update') }}" data-ajax novalidate style="max-width: 640px">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Nombre del negocio *</label>
        <input class="form-control" name="name" value="{{ $business->name }}" required>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">RUC</label>
            <input class="form-control" name="ruc" value="{{ $business->ruc }}" inputmode="numeric" maxlength="11" placeholder="20123456789">
        </div>
        <div class="col-md-6">
            <label class="form-label">Rubro *</label>
            <select class="form-select" name="industry">
                @foreach ($industries as $value => $label)
                    <option value="{{ $value }}" @selected($business->industry === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">¿Cómo se llaman tus puntos? *</label>
            <input class="form-control" name="points_label" value="{{ $business->points_label }}" placeholder="Puntos, Estrellas, Sellos...">
        </div>
        <div class="col-md-6">
            <label class="form-label">Color de la marca *</label>
            <input class="form-control form-control-color w-100" type="color" name="primary_color" value="{{ $business->primary_color }}">
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label">Moneda</label>
            <input class="form-control" value="{{ $business->currency_symbol }} ({{ $business->currency }}) — Sol peruano" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label">Zona horaria</label>
            <input class="form-control" value="{{ $business->timezone }}" disabled>
        </div>
    </div>
    <div>
        <button type="submit" class="btn btn-primary px-4">Guardar cambios</button>
    </div>
</form>
@endsection
