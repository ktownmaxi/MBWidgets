<?php

namespace FluxErp\Models;

use FluxErp\Casts\Money;
use FluxErp\Casts\Percentage;
use FluxErp\Contracts\OffersPrinting;
use FluxErp\Models\Pivots\AddressAddressTypeOrder;
use FluxErp\States\Order\DeliveryState\DeliveryState;
use FluxErp\States\Order\OrderState;
use FluxErp\States\Order\PaymentState\Open;
use FluxErp\States\Order\PaymentState\Paid;
use FluxErp\States\Order\PaymentState\PartialPaid;
use FluxErp\States\Order\PaymentState\PaymentState;
use FluxErp\Support\Calculation\Rounding;
use FluxErp\Support\Collection\OrderCollection;
use FluxErp\Traits\Commentable;
use FluxErp\Traits\Communicatable;
use FluxErp\Traits\Filterable;
use FluxErp\Traits\HasAdditionalColumns;
use FluxErp\Traits\HasClientAssignment;
use FluxErp\Traits\HasCustomEvents;
use FluxErp\Traits\HasFrontendAttributes;
use FluxErp\Traits\HasPackageFactory;
use FluxErp\Traits\HasRelatedModel;
use FluxErp\Traits\HasSerialNumberRange;
use FluxErp\Traits\HasUserModification;
use FluxErp\Traits\HasUuid;
use FluxErp\Traits\InteractsWithMedia;
use FluxErp\Traits\Printable;
use FluxErp\Traits\Scout\Searchable;
use FluxErp\Traits\SoftDeletes;
use FluxErp\Traits\Trackable;
use FluxErp\View\Printing\Order\Invoice;
use FluxErp\View\Printing\Order\Offer;
use FluxErp\View\Printing\Order\OrderConfirmation;
use FluxErp\View\Printing\Order\Refund;
use FluxErp\View\Printing\Order\Retoure;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\ModelStates\HasStates;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;

class Order extends Model implements HasMedia, InteractsWithDataTables, OffersPrinting
{
    use BroadcastsEvents, Commentable, Communicatable, Filterable, HasAdditionalColumns, HasClientAssignment,
        HasCustomEvents, HasFrontendAttributes, HasPackageFactory, HasRelatedModel, HasSerialNumberRange, HasStates,
        HasUserModification, HasUuid, InteractsWithMedia, Printable, Searchable, SoftDeletes, Trackable {
            Printable::resolvePrintViews as protected printableResolvePrintViews;
        }

    protected $with = [
        'currency',
    ];

    protected ?string $detailRouteName = 'orders.id';

    protected $guarded = [
        'id',
    ];

    public static string $iconName = 'shopping-bag';

    protected static function booted(): void
    {
        static::saving(function (Order $order) {
            if ($order->isDirty('address_invoice_id')) {
                $addressInvoice = $order->addressInvoice()->first();
                $order->address_invoice = $addressInvoice;

                // Get additional attributes from address if not explicitly changed
                $order->language_id = $order->isDirty('language_id')
                    ? $order->language_id
                    : $addressInvoice->language_id;
                $order->contact_id = $order->isDirty('contact_id')
                    ? $order->contact_id
                    : $addressInvoice->contact_id;

                $contact = $order->contact()->first();
                $order->price_list_id = ! $contact->price_list_id || $order->isDirty('price_list_id')
                    ? $order->price_list_id
                    : $contact->price_list_id;
                $order->payment_type_id = ! $contact->payment_type_id || $order->isDirty('payment_type_id')
                    ? $order->payment_type_id
                    : $contact->payment_type_id;
                $order->client_id = ! $contact->client_id || $order->isDirty('client_id')
                    ? $order->client_id
                    : $contact->client_id;
            }

            if ($order->isDirty('address_delivery_id')
                && $order->address_delivery_id
                && ! $order->isDirty('address_delivery')
            ) {
                $order->address_delivery = $order->addressDelivery()->first();
            }

            // reset to original
            if ($order->wasChanged(['order_number', 'invoice_number'])) {
                $order->order_number = $order->getOriginal('order_number');
                $order->invoice_number = $order->getOriginal('invoice_number');
            }

            if (! $order->exists && ! $order->order_number) {
                $order->getSerialNumber('order_number');
            }

            if ($order->isDirty('invoice_number')) {
                $order->calculateBalance();

                if (is_null($order->payment_reminder_next_date) && ! is_null($order->invoice_date)) {
                    $order->payment_reminder_next_date = $order->invoice_date->addDays(
                        $order->payment_reminder_days_1
                    );
                }
            }

            if ($order->isDirty('iban')
                && $order->iban
                && str_replace(' ', '', strtoupper($order->iban)) !== $order->contactBankConnection?->iban
            ) {
                $order->contact_bank_connection_id = null;
            }

            if (
                $order->contact_bank_connection_id
                && $order->isDirty('contact_bank_connection_id')
                && ! $order->isDirty('iban')
            ) {
                $order->iban = $order->contactBankConnection->iban;
                $order->account_holder = $order->contactBankConnection->account_holder;
                $order->bank_name = $order->contactBankConnection->bank_name;
                $order->bic = $order->contactBankConnection->bic;
            }

            if ($order->isDirty('iban') && $order->iban) {
                $order->iban = str_replace(' ', '', strtoupper($order->iban));
            }
        });

        static::deleted(function (Order $order) {
            $order->orderPositions()->delete();
            $order->purchaseInvoice()->update(['order_id' => null]);
        });
    }

    protected function casts(): array
    {
        return [
            'address_invoice' => 'array',
            'address_delivery' => 'array',
            'state' => OrderState::class,
            'payment_state' => PaymentState::class,
            'delivery_state' => DeliveryState::class,
            'shipping_costs_net_price' => Money::class,
            'shipping_costs_gross_price' => Money::class,
            'shipping_costs_vat_price' => Money::class,
            'shipping_costs_vat_rate_percentage' => Percentage::class,
            'total_base_gross_price' => Money::class,
            'total_base_net_price' => Money::class,
            'total_purchase_price' => Money::class,
            'total_cost' => Money::class,
            'margin' => Money::class,
            'total_gross_price' => Money::class,
            'total_net_price' => Money::class,
            'total_vats' => 'array',
            'balance' => Money::class,
            'payment_reminder_next_date' => 'date',
            'payment_texts' => 'array',
            'order_date' => 'date',
            'invoice_date' => 'date',
            'system_delivery_date' => 'date',
            'system_delivery_date_end' => 'date',
            'customer_delivery_date' => 'date',
            'date_of_approval' => 'date',
            'has_logistic_notify_phone_number' => 'boolean',
            'has_logistic_notify_number' => 'boolean',
            'is_locked' => 'boolean',
            'is_new_customer' => 'boolean',
            'is_imported' => 'boolean',
            'is_merge_invoice' => 'boolean',
            'is_confirmed' => 'boolean',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'address_address_type_order')
            ->using(AddressAddressTypeOrder::class)
            ->withPivot(['address_type_id', 'address']);
    }

    public function addressDelivery(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_delivery_id');
    }

    public function addressInvoice(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_invoice_id');
    }

    public function addressTypes(): BelongsToMany
    {
        return $this->belongstoMany(AddressType::class, 'address_address_type_order')
            ->withPivot('address_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function contactBankConnection(): BelongsTo
    {
        return $this->belongsTo(ContactBankConnection::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function orderPositions(): HasMany
    {
        return $this->hasMany(OrderPosition::class);
    }

    public function orderType(): BelongsTo
    {
        return $this->belongsTo(OrderType::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_id');
    }

    public function paymentReminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function paymentRuns(): BelongsToMany
    {
        return $this->belongsToMany(PaymentRun::class, 'order_payment_run');
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function purchaseInvoice(): HasOne
    {
        return $this->hasOne(PurchaseInvoice::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Project::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'order_user');
    }

    public function vatRates(): HasManyThrough
    {
        return $this->hasManyThrough(
            VatRate::class,
            OrderPosition::class,
            'order_id',
            'id',
            'id',
            'vat_rate_id'
        );
    }

    public function newCollection(array $models = []): Collection
    {
        return app(OrderCollection::class, ['items' => $models]);
    }

    public function calculatePaymentState(): static
    {
        if (! $this->transactions()->exists()) {
            if ($this->payment_state->canTransitionTo(Open::class)) {
                $this->payment_state->transitionTo(Open::class);
            }
        } else {
            if (
                bccomp(
                    bcround($this->transactions()->sum('amount'), 2),
                    bcround($this->total_gross_price, 2),
                    2
                ) === 0
            ) {
                if ($this->payment_state->canTransitionTo(Paid::class)) {
                    $this->payment_state->transitionTo(Paid::class);
                }
            } else {
                if ($this->payment_state->canTransitionTo(PartialPaid::class)) {
                    $this->payment_state->transitionTo(PartialPaid::class);
                }
            }
        }

        $this->calculateBalance();

        return $this;
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(
            [
                'addresses',
            ]
        );
    }

    public function invoice(): ?\Spatie\MediaLibrary\MediaCollections\Models\Media
    {
        return $this->getFirstMedia('invoice');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('invoice')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/xml', 'text/xml'])
            ->singleFile();

        $this->addMediaCollection('payment-reminders')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('signature')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->useDisk('local');
    }

    public function calculatePrices(): static
    {
        return $this->calculateTotalGrossPrice()
            ->calculateTotalNetPrice()
            ->calculateMargin()
            ->calculateTotalVats();
    }

    public function calculateTotalGrossPrice(): static
    {
        $totalGross = $this->orderPositions()
            ->where('is_alternative', false)
            ->sum('total_gross_price');
        $totalBaseGross = $this->orderPositions()
            ->where('is_alternative', false)
            ->sum('total_base_gross_price');

        $this->total_gross_price = bcround(
            bcadd($totalGross, $this->shipping_costs_gross_price ?: 0, 9),
            2
        );
        $this->total_base_gross_price = bcround(
            bcadd($totalBaseGross, $this->shipping_costs_gross_price ?: 0, 9),
            2
        );

        return $this;
    }

    public function calculateTotalNetPrice(): static
    {
        $totalNet = $this->orderPositions()
            ->where('is_alternative', false)
            ->sum('total_net_price');
        $totalBaseNet = $this->orderPositions()
            ->where('is_alternative', false)
            ->sum('total_base_net_price');

        $this->total_net_price = bcround(
            bcadd($totalNet, $this->shipping_costs_net_price ?: 0, 9),
            2
        );
        $this->total_base_net_price = bcround(
            bcadd($totalBaseNet, $this->shipping_costs_net_price ?: 0, 9),
            2
        );

        return $this;
    }

    public function calculateTotalVats(): static
    {
        $totalVats = $this->orderPositions()
            ->where('is_alternative', false)
            ->whereNotNull('vat_rate_percentage')
            ->groupBy('vat_rate_percentage')
            ->selectRaw('sum(vat_price) as total_vat_price, sum(total_net_price) as total_net_price, vat_rate_percentage')
            ->get()
            ->map(function (OrderPosition $item) {
                return $item->only(
                    [
                        'vat_rate_percentage',
                        'total_vat_price',
                        'total_net_price',
                    ]
                );
            })
            ->keyBy('vat_rate_percentage');

        if ($this->shipping_costs_vat_price) {
            $totalVats->put(
                $this->shipping_costs_vat_rate_percentage,
                [
                    'total_vat_price' => bcadd(
                        $this->shipping_costs_vat_price,
                        $totalVats->get($this->shipping_costs_vat_rate_percentage)['total_vat_price'] ?? 0,
                        9
                    ),
                    'total_net_price' => bcadd(
                        $this->shipping_costs_net_price,
                        $totalVats->get($this->shipping_costs_vat_rate_percentage)['total_net_price'] ?? 0,
                        9
                    ),
                    'vat_rate_percentage' => $this->shipping_costs_vat_rate_percentage,
                ]
            );
        }

        $this->total_vats = $totalVats->sortBy('vat_rate_percentage')->values();

        return $this;
    }

    public function calculateBalance(): static
    {
        $this->balance = bcround(
            bcsub($this->total_gross_price, $this->transactions()->sum('amount'), 9),
            2
        );

        return $this;
    }

    public function calculateMargin(): static
    {
        $this->total_purchase_price = $this->orderPositions()
            ->where('is_alternative', false)
            ->sum('purchase_price');

        $this->margin = Rounding::round(
            bcsub($this->total_net_price, $this->total_purchase_price, 9),
            2
        );

        $variableCosts = 0;
        $variableCosts = bcadd($variableCosts, $this->commissions()->sum('commission'));
        $variableCosts = bcadd($variableCosts, $this->workTimes()->sum('total_cost'));
        $variableCosts = bcadd($variableCosts, $this->projects()->sum('total_cost'));
        $this->total_cost = $variableCosts;

        $this->gross_profit = Rounding::round(
            bcsub($this->margin, $this->total_cost, 9),
            2
        );

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->orderType?->name . ' - ' . $this->order_number . ' - ' . data_get($this->address_invoice, 'name');
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
        return $this->contact?->getAvatarUrl() ?: self::icon()->getUrl();
    }

    public function getPortalDetailRoute(): string
    {
        return route('portal.orders.id', ['id' => $this->id]);
    }

    public function getPrintViews(): array
    {
        return $this->orderType?->order_type_enum->isPurchase()
            ? []
            : [
                'invoice' => Invoice::class,
                'offer' => Offer::class,
                'order-confirmation' => OrderConfirmation::class,
                'retoure' => Retoure::class,
                'refund' => Refund::class,
            ];
    }

    public function resolvePrintViews(): array
    {
        $printViews = $this->printableResolvePrintViews();

        return array_intersect_key($printViews, array_flip($this->orderType?->print_layouts ?: []));
    }

    public function costColumn(): ?string
    {
        return 'total_cost';
    }
}
