<?php

namespace FluxErp\Actions\Warehouse;

use FluxErp\Actions\FluxAction;
use FluxErp\Models\Warehouse;
use FluxErp\Rulesets\Warehouse\DeleteWarehouseRuleset;
use Illuminate\Validation\ValidationException;

class DeleteWarehouse extends FluxAction
{
    protected function boot(array $data): void
    {
        parent::boot($data);
        $this->rules = resolve_static(DeleteWarehouseRuleset::class, 'getRules');
    }

    public static function models(): array
    {
        return [Warehouse::class];
    }

    public function performAction(): ?bool
    {
        return app(Warehouse::class)->query()
            ->whereKey($this->data['id'])
            ->first()
            ->delete();
    }

    protected function validateData(): void
    {
        parent::validateData();

        if (app(Warehouse::class)->query()
            ->whereKey($this->data['id'])
            ->first()
            ->stockPostings()
            ->count() > 0
        ) {
            throw ValidationException::withMessages([
                'stock_postings' => [__('The given warehouse has stock postings')],
            ])->errorBag('deleteWarehouse');
        }
    }
}
