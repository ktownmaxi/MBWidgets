<?php

namespace FluxErp\Actions\Warehouse;

use FluxErp\Actions\BaseAction;
use FluxErp\Http\Requests\UpdateWarehouseRequest;
use FluxErp\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

class UpdateWarehouse extends BaseAction
{
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->rules = (new UpdateWarehouseRequest())->rules();
    }

    public static function models(): array
    {
        return [Warehouse::class];
    }

    public function execute(): Model
    {
        $warehouse = Warehouse::query()
            ->whereKey($this->data['id'])
            ->first();

        $warehouse->fill($this->data);
        $warehouse->save();

        return $warehouse->withoutRelations()->fresh();
    }
}
