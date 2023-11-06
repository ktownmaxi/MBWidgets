<?php

namespace FluxErp\Livewire\DataTables;

use FluxErp\Models\SerialNumber;
use Illuminate\Database\Eloquent\Builder;
use TeamNiftyGmbH\DataTable\DataTable;

class SerialNumberList extends DataTable
{
    protected string $model = SerialNumber::class;

    public array $enabledCols = [
        'id',
        'avatar',
        'product.name',
        'serial_number',
        'customer',
    ];

    public array $availableRelations = ['*'];

    public array $sortable = ['*'];

    public array $aggregatable = ['*'];

    public array $availableCols = ['*'];

    public array $formatters = [
        'avatar' => 'image',
    ];

    public function getBuilder(Builder $builder): Builder
    {
        return $builder->with([
            'product:id,name',
            'product.media',
            'address:id,firstname,lastname,company',
        ]);
    }

    public function itemToArray($item): array
    {
        $returnArray = parent::itemToArray($item);
        $returnArray['avatar'] = $item->product?->getAvatarUrl();
        $returnArray['customer'] = $item->address?->getLabel();

        return $returnArray;
    }
}
