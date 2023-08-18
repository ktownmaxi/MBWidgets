<?php

namespace FluxErp\Http\Requests;

use FluxErp\Models\Category;
use FluxErp\Rules\ClassExists;
use Illuminate\Database\Eloquent\Model;

class CreateCategoryRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            (new Category())->hasAdditionalColumnsValidationRules(),
            [
                'uuid' => 'string|uuid|unique:categories,uuid',
                'model_type' => [
                    'required',
                    'string',
                    new ClassExists(instanceOf: Model::class),
                ],
                'name' => 'required|string',
                'parent_id' => 'integer|nullable|exists:categories,id',
                'is_active' => 'boolean',
            ],
        );
    }
}
