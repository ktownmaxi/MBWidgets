<?php

namespace FluxErp\Actions\VatRate;

use FluxErp\Actions\BaseAction;
use FluxErp\Http\Requests\UpdateVatRateRequest;
use FluxErp\Models\VatRate;
use Illuminate\Database\Eloquent\Model;

class UpdateVatRate extends BaseAction
{
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->rules = (new UpdateVatRateRequest())->rules();
    }

    public static function models(): array
    {
        return [VatRate::class];
    }

    public function execute(): Model
    {
        $vatRate = VatRate::query()
            ->whereKey($this->data['id'])
            ->first();

        $vatRate->fill($this->data);
        $vatRate->save();

        return $vatRate->withoutRelations()->fresh();
    }
}
