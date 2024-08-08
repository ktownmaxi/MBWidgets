<?php

namespace FluxErp\Models;

use FluxErp\Traits\Filterable;
use FluxErp\Traits\HasClientAssignment;
use FluxErp\Traits\HasPackageFactory;
use FluxErp\Traits\HasUuid;
use FluxErp\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BankConnection extends Model
{
    use Filterable, HasClientAssignment, HasPackageFactory, HasUuid, LogsActivity;

    protected $guarded = [
        'id',
    ];

    protected static function booted(): void
    {
        static::saving(function (BankConnection $model) {
            if ($model->isDirty('iban') && ! is_null($model->iban)) {
                $model->iban = str_replace(' ', '', strtoupper($model->iban));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'bank_connection_client');
    }
}
