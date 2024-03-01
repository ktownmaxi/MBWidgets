<?php

namespace FluxErp\Actions\Product;

use FluxErp\Actions\FluxAction;
use FluxErp\Models\Product;
use FluxErp\Rulesets\Product\DeleteProductRuleset;
use Illuminate\Validation\ValidationException;

class DeleteProduct extends FluxAction
{
    protected function boot(array $data): void
    {
        parent::boot($data);
        $this->rules = resolve_static(DeleteProductRuleset::class, 'getRules');
    }

    public static function models(): array
    {
        return [Product::class];
    }

    public function performAction(): ?bool
    {
        return app(Product::class)->query()
            ->whereKey($this->data['id'])
            ->first()
            ->delete();
    }

    protected function validateData(): void
    {
        parent::validateData();

        if (app(Product::class)->query()
            ->whereKey($this->data['id'])
            ->first()
            ->children()
            ->count() > 0
        ) {
            throw ValidationException::withMessages([
                'children' => [__('The given product has children')],
            ])->errorBag('deleteProduct');
        }
    }
}
