<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use App\Support\CurrentBusiness;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Para todo modelo que tenga columna business_id:
 *  - aplica el BusinessScope (solo ve datos del negocio actual)
 *  - al crear, rellena business_id con el negocio actual si no se indicó
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model) {
            if (empty($model->business_id)) {
                $model->business_id = app(CurrentBusiness::class)->id();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
