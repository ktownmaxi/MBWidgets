<?php

namespace FluxErp\Http\Requests;

use FluxErp\Rules\Iban;

class CreateContactBankConnectionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'uuid' => 'string|uuid|unique:bank_connections,uuid',
            'contact_id' => 'integer|nullable|exists:contacts,id,deleted_at,NULL',
            'iban' => ['required', 'string', new Iban()],
            'account_holder' => 'string|nullable',
            'bank_name' => 'string|nullable',
            'bic' => 'string|nullable',
        ];
    }
}
