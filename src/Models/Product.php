<?php

namespace FluxErp\Models;

use FluxErp\Traits\Filterable;
use FluxErp\Traits\HasAdditionalColumns;
use FluxErp\Traits\HasFrontendAttributes;
use FluxErp\Traits\HasPackageFactory;
use FluxErp\Traits\HasSerialNumberRange;
use FluxErp\Traits\HasUserModification;
use FluxErp\Traits\HasUuid;
use FluxErp\Traits\InteractsWithMedia;
use FluxErp\Traits\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;

class Product extends Model implements HasMedia, InteractsWithDataTables
{
    use Filterable, HasAdditionalColumns, HasPackageFactory, HasFrontendAttributes, HasSerialNumberRange, HasUserModification,
        HasUuid, InteractsWithMedia, Searchable, SoftDeletes;

    protected $hidden = [
        'uuid',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
        'is_shipping_free' => 'boolean',
        'is_bundle' => 'boolean',
        'is_required_product_serial_number' => 'boolean',
        'is_required_manufacturer_serial_number' => 'boolean',
        'is_auto_create_serial_number' => 'boolean',
        'is_product_serial_number' => 'boolean',
        'is_nos' => 'boolean',
        'is_active_export_to_web_shop' => 'boolean',
    ];

    protected $guarded = [
        'id',
        'uuid',
    ];

    public array $translatable = [
        'name',
        'description',
    ];

    protected string $detailRouteName = 'products.id?';

    public static string $iconName = 'square-3-stack-3d';

    public function bundleProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_bundle_product', 'product_id', 'bundle_product_id')
            ->withPivot('count');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function productOptions(): BelongsToMany
    {
        return $this->belongsToMany(ProductOption::class, 'product_product_option');
    }

    public function productProperties(): BelongsToMany
    {
        return $this->belongsToMany(ProductProperty::class, 'product_product_property', 'product_id', 'product_prop_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->useDisk('public');
    }

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
        return $this->getFirstMediaUrl('images') ?: self::icon()->getUrl();
    }
}
