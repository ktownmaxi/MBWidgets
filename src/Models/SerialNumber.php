<?php

namespace FluxErp\Models;

use FluxErp\Traits\Commentable;
use FluxErp\Traits\Filterable;
use FluxErp\Traits\HasAdditionalColumns;
use FluxErp\Traits\HasFrontendAttributes;
use FluxErp\Traits\HasPackageFactory;
use FluxErp\Traits\HasUserModification;
use FluxErp\Traits\HasUuid;
use FluxErp\Traits\InteractsWithMedia;
use FluxErp\Traits\LogsActivity;
use FluxErp\Traits\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\MediaLibrary\HasMedia;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;

class SerialNumber extends FluxModel implements HasMedia, InteractsWithDataTables
{
    use Commentable, Filterable, HasAdditionalColumns, HasFrontendAttributes, HasPackageFactory, HasUserModification,
        HasUuid, InteractsWithMedia, LogsActivity, Searchable;

    protected $guarded = [
        'id',
    ];

    protected ?string $detailRouteName = 'products.serial-numbers.id?';

    public static string $iconName = 'tag';

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'address_serial_number')->withPivot('quantity');
    }

    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(Product::class, StockPosting::class, 'serial_number_id', 'id', 'id', 'product_id');
    }

    public function stockPostings(): HasMany
    {
        return $this->hasMany(StockPosting::class);
    }

    public function getLabel(): ?string
    {
        return $this->serial_number . ' - ' . $this->product?->name;
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function getUrl(): ?string
    {
        return $this->detailRoute();
    }

    /**
     * @throws \Exception
     */
    public function getAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar') ?: static::icon()->getUrl();
    }
}
