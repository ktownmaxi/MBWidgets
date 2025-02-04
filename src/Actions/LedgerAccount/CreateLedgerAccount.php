<?php

namespace FluxErp\Actions\LedgerAccount;

use FluxErp\Actions\FluxAction;
use FluxErp\Models\Client;
use FluxErp\Models\LedgerAccount;
use FluxErp\Rulesets\LedgerAccount\CreateLedgerAccountRuleset;
use Illuminate\Validation\ValidationException;

class CreateLedgerAccount extends FluxAction
{
    protected function getRulesets(): string|array
    {
        return CreateLedgerAccountRuleset::class;
    }

    public static function models(): array
    {
        return [LedgerAccount::class];
    }

    public function performAction(): mixed
    {
        $ledgerAccount = app(LedgerAccount::class, ['attributes' => $this->data]);
        $ledgerAccount->save();

        return $ledgerAccount->fresh();
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->data['client_id'] ??= Client::default()->getKey();
    }

    protected function validateData(): void
    {
        parent::validateData();

        if (resolve_static(LedgerAccount::class, 'query')
            ->where('number', $this->getData('number'))
            ->where('ledger_account_type_enum', $this->getData('ledger_account_type_enum'))
            ->where('client_id', $this->getData('client_id'))
            ->exists()
        ) {
            throw ValidationException::withMessages(['number' => 'The number has already been taken for this type.']);
        }
    }
}
