<?php

namespace FluxErp\Actions\Price;

use FluxErp\Actions\FluxAction;
use FluxErp\Models\Price;
use FluxErp\Rulesets\Price\DeletePriceRuleset;
use Illuminate\Validation\ValidationException;

class DeletePrice extends FluxAction
{
    protected function boot(array $data): void
    {
        parent::boot($data);
        $this->rules = resolve_static(DeletePriceRuleset::class, 'getRules');
    }

    public static function models(): array
    {
        return [Price::class];
    }

    public function performAction(): ?bool
    {
        return app(Price::class)->query()
            ->whereKey($this->data['id'])
            ->first()
            ->delete();
    }

    protected function validateData(): void
    {
        parent::validateData();

        if (app(Price::class)->query()
            ->whereKey($this->data['id'])
            ->first()
            ->orderPositions()
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'order_positions' => [__('Price has associated order positions')],
            ])->errorBag('deletePrice');
        }
    }
}
