<?php

namespace FluxErp\Http\Requests;

use FluxErp\Enums\OrderTypeEnum;
use Illuminate\Validation\Rules\Enum;

class CreateOrderTypeRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'uuid' => 'string|uuid|unique:order_types,uuid',
            'client_id' => 'required|integer|exists:clients,id,deleted_at,NULL',
            'name' => 'required|string',
            'description' => 'string|nullable',
            'print_layouts' => 'array|nullable',
            'print_layouts.*' => 'required|string',
            'order_type_enum' => [
                'required',
                new Enum(OrderTypeEnum::class),
            ],
            'is_active' => 'boolean',
            'is_hidden' => 'boolean',
        ];
    }
}
