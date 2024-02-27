<?php

namespace FluxErp\Models;

use FluxErp\Traits\Commentable;
use FluxErp\Traits\HasAdditionalColumns;
use FluxErp\Traits\HasClientAssignment;
use FluxErp\Traits\HasFrontendAttributes;
use FluxErp\Traits\HasPackageFactory;
use FluxErp\Traits\HasSerialNumberRange;
use FluxErp\Traits\HasUserModification;
use FluxErp\Traits\HasUuid;
use FluxErp\Traits\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\JoinClause;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Tags\HasTags;
use TeamNiftyGmbH\DataTable\Casts\BcFloat;
use TeamNiftyGmbH\DataTable\Casts\Money;
use TeamNiftyGmbH\DataTable\Casts\Percentage;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;

class OrderPosition extends Model implements InteractsWithDataTables, Sortable
{
    use Commentable, HasAdditionalColumns, HasClientAssignment, HasFrontendAttributes, HasPackageFactory,
        HasSerialNumberRange, HasTags, HasUserModification, HasUuid, SoftDeletes, SortableTrait;

    protected $appends = [
        'unit_price',
    ];

    protected $casts = [
        'uuid' => 'string',
        'product_prices' => 'array',
        'total_net_price' => Money::class,
        'total_gross_price' => Money::class,
        'unit_net_price' => Money::class,
        'unit_gross_price' => Money::class,
        'vat_price' => Money::class,
        'margin' => Money::class,
        'provision' => Money::class,
        'purchase_price' => Money::class,
        'total_base_price' => Money::class,
        'total_base_gross_price' => Money::class,
        'total_base_net_price' => Money::class,
        'vat_rate_percentage' => Percentage::class,
        'discount_percentage' => Percentage::class,
        'amount' => BcFloat::class,
        'is_alternative' => 'boolean',
        'is_net' => 'boolean',
        'is_free_text' => 'boolean',
        'is_bundle_position' => 'boolean',
        'is_positive_operator' => 'boolean',
    ];

    protected $guarded = [
        'id',
    ];

    public array $sortable = [
        'order_column_name' => 'sort_number',
        'sort_when_creating' => true,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('withChildren', function ($builder) {
            $builder->with('children');
        });
    }

    public function getChildrenAttribute(): Collection
    {
        return $this->children()->get()->append('children');
    }

    public function getTagsAttribute(): Collection
    {
        return $this->tags()->get();
    }

    protected function totalNetPrice(): Attribute
    {
        return Attribute::get(
            fn ($value) => $this->is_free_text && ! $value
                ? $this->subTotalNet() ?: null
                : $value
        );
    }

    protected function totalGrossPrice(): Attribute
    {
        return Attribute::get(
            fn ($value) => $this->is_free_text && ! $value
                ? $this->subTotalGross() ?: null
                : $value
        );
    }

    protected function unitPrice(): Attribute
    {
        return Attribute::get(
            fn ($value) => $this->is_net
                ? $this->unit_net_price
                : $this->unit_gross_price
        );
    }

    public function ancestors(): HasMany
    {
        return $this->hasMany(OrderPosition::class, 'origin_position_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrderPosition::class, 'parent_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function currency(): HasOneThrough
    {
        return $this->hasOneThrough(
            Currency::class,
            Order::class,
            'id',
            'id',
            'order_id',
            'currency_id'
        );
    }

    public function discounts(): MorphMany
    {
        return $this->morphMany(Discount::class, 'model');
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(OrderPosition::class, 'origin_position_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrderPosition::class, 'parent_id');
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class);
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    public function workTime(): HasOne
    {
        return $this->hasOne(WorkTime::class);
    }

    private function subTotalNet(): string
    {
        return (string) (($this->relations['children'] ?? $this->children())
            ->where('is_alternative', false)
            ->sum('total_net_price'));
    }

    private function subTotalGross(): string
    {
        return (string) (($this->relations['children'] ?? $this->children())
            ->where('is_alternative', false)
            ->sum('total_gross_price'));
    }

    public function buildSortQuery(): Builder
    {
        return static::query()
            ->where('order_id', $this->order_id);
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
        return $this->order?->getUrl();
    }

    public function getAvatarUrl(): ?string
    {
        return $this->product?->getAvatarUrl();
    }

    public function scopeDescendants(Builder $query): void
    {
        $tableName = $this->getTable();
        $subQuery = $this->newQuery()
            ->select([$tableName . '.id', $tableName . '.amount'])
            ->selectRaw('SUM(COALESCE(descendants.amount, 0)) AS descendantAmount')
            ->leftJoin($tableName . ' AS descendants', function (JoinClause $join) use ($tableName) {
                $join->on($tableName . '.id', '=', 'descendants.parent_id')
                    ->whereNull('descendants.deleted_at');
            })
            ->where($tableName . '.is_bundle_position', false)
            ->groupBy($tableName . '.id');

        $query->leftJoinSub($subQuery, 'sub', fn (JoinClause $join) => $join->on($tableName . '.id', '=', 'sub.id'))
            ->addSelect('sub.descendantAmount');
    }

    public function scopeSiblings(Builder $query): void
    {
        $tableName = $this->getTable();
        $subQuery = $this->newQuery()
            ->select($tableName . '.id')
            ->selectRaw('SUM(COALESCE(siblings.amount, 0)) AS siblingAmount')
            ->selectRaw('SUM(COALESCE(' . $tableName . '.amount, 0)) - SUM(COALESCE(siblings.amount, 0))
                AS totalAmount'
            )
            ->leftJoin($tableName . ' AS siblings', function (JoinClause $join) use ($tableName) {
                $join->on($tableName . '.id', '=', 'siblings.origin_position_id')
                    ->whereNull('siblings.deleted_at');
            })
            ->where($tableName . '.is_bundle_position', false)
            ->groupBy($tableName . '.id');

        $query->leftJoinSub($subQuery, 'sub', fn ($join) => $join->on($tableName . '.id', '=', 'sub.id'))
            ->addSelect(['sub.siblingAmount', 'sub.totalAmount', $tableName . '.id'])
            ->whereRaw($tableName . '.amount > sub.siblingAmount');
    }
}
