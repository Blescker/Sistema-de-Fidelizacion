<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToBusiness;

    public const UPDATED_AT = null;

    protected $fillable = [
        'business_id', 'user_id', 'action', 'auditable_type', 'auditable_id',
        'old_values', 'new_values', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Registra una acción. Ej: AuditLog::record('branch.updated', $branch, $antes, $despues)
     */
    public static function record(string $action, ?Model $model = null, ?array $old = null, ?array $new = null): self
    {
        $hidden = ['password', 'remember_token'];

        $businessId = $model instanceof Business
            ? $model->getKey()
            : ($model?->getAttribute('business_id') ?? auth()->user()?->business_id);

        return static::create([
            'business_id' => $businessId,
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'old_values' => $old ? array_diff_key($old, array_flip($hidden)) : null,
            'new_values' => $new ? array_diff_key($new, array_flip($hidden)) : null,
            'ip' => request()?->ip(),
        ]);
    }
}
