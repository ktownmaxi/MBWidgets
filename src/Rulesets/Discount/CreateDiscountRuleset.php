<?php

namespace FluxErp\Rulesets\Discount;

use FluxErp\Models\Discount;
use FluxErp\Rules\MorphClassExists;
use FluxErp\Rules\MorphExists;
use FluxErp\Rulesets\FluxRuleset;

class CreateDiscountRuleset extends FluxRuleset
{
    protected static ?string $model = Discount::class;

    public function rules(): array
    {
        return [
            'uuid' => 'string|uuid|unique:discounts,uuid',
            'model_type' => [
                'required_with:model_id',
                'string',
                new MorphClassExists(),
            ],
            'model_id' => [
                'required_with:model_type',
                'integer',
                new MorphExists(),
            ],
            'discount' => 'required|numeric',
            'from' => 'nullable|date_format:Y-m-d H:i:s',
            'till' => 'nullable|date_format:Y-m-d H:i:s',
            'sort_number' => 'integer|min:0',
            'is_percentage' => 'required|boolean',
        ];
    }
}
