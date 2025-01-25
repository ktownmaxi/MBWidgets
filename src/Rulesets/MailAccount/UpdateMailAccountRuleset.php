<?php

namespace FluxErp\Rulesets\MailAccount;

use FluxErp\Models\MailAccount;
use FluxErp\Rules\ModelExists;
use FluxErp\Rulesets\FluxRuleset;

class UpdateMailAccountRuleset extends FluxRuleset
{
    protected static ?string $model = MailAccount::class;

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                app(ModelExists::class, ['model' => MailAccount::class]),
            ],
            'protocol' => 'sometimes|required|string|max:255|in:imap,pop3,nntp',
            'password' => 'nullable|string|max:255|exclude_if:password,null',
            'host' => 'sometimes|required|string|max:255',
            'port' => 'integer',
            'encryption' => 'sometimes|string|max:255|in:ssl,tls',
            'smtp_mailer' => 'nullable|string|max:255',
            'smtp_email' => 'string',
            'smtp_password' => 'nullable|string|max:255|exclude_if:smtp_password,null',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'integer',
            'smtp_encryption' => 'nullable|string|max:255|in:ssl,tls',
            'is_auto_assign' => 'boolean',
            'is_o_auth' => 'boolean',
            'has_valid_certificate' => 'boolean',
        ];
    }
}
