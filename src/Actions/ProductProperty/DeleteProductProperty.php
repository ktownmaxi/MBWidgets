<?php

namespace FluxErp\Actions\ProductProperty;

use FluxErp\Actions\FluxAction;
use FluxErp\Models\ProductProperty;
use FluxErp\Rulesets\ProductProperty\DeleteProductPropertyRuleset;
use Illuminate\Validation\ValidationException;

class DeleteProductProperty extends FluxAction
{
    protected function boot(array $data): void
    {
        parent::boot($data);
        $this->rules = resolve_static(DeleteProductPropertyRuleset::class, 'getRules');
    }

    public static function models(): array
    {
        return [ProductProperty::class];
    }

    public function performAction(): ?bool
    {
        return app(ProductProperty::class)->query()
            ->whereKey($this->data['id'])
            ->first()
            ->delete();
    }

    protected function validateData(): void
    {
        parent::validateData();

        if (app(ProductProperty::class)->query()
            ->whereKey($this->data['id'])
            ->first()
            ->products()
            ->count() > 0
        ) {
            throw ValidationException::withMessages([
                'products' => [__('Product property has products')],
            ])->errorBag('deleteProductProperty');
        }
    }
}
