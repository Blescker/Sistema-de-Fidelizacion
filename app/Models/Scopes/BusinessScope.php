<?php

namespace App\Models\Scopes;

use App\Support\CurrentBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtra automáticamente toda consulta por el negocio actual:
 * Branch::all() se convierte en "... where business_id = <negocio actual>".
 * Así un negocio nunca ve datos de otro aunque alguien olvide el where.
 */
class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $businessId = app(CurrentBusiness::class)->id();

        if ($businessId !== null) {
            $builder->where($model->qualifyColumn('business_id'), $businessId);
        }
    }
}
