<?php

namespace FluxErp\Actions\MailAccount;

use FluxErp\Actions\FluxAction;
use FluxErp\Http\Requests\UpdateMailAccountRequest;
use FluxErp\Models\MailAccount;
use Illuminate\Database\Eloquent\Model;

class UpdateMailAccount extends FluxAction
{
    protected function boot(array $data): void
    {
        parent::boot($data);
        $this->rules = (new UpdateMailAccountRequest())->rules();
    }

    public static function models(): array
    {
        return [MailAccount::class];
    }

    public function performAction(): Model
    {
        $mailAccount = MailAccount::query()
            ->whereKey($this->data['id'])
            ->first();

        $mailAccount->fill($this->data);
        $mailAccount->save();

        return $mailAccount->withoutRelations()->fresh();
    }
}
