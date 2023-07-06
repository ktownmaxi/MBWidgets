<?php

namespace FluxErp\Actions\PaymentType;

use FluxErp\Contracts\ActionInterface;
use FluxErp\Http\Requests\UpdatePaymentTypeRequest;
use FluxErp\Models\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class UpdatePaymentType implements ActionInterface
{
    private array $data;

    private array $rules;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->rules = (new UpdatePaymentTypeRequest())->rules();
    }

    public static function make(array $data): static
    {
        return new static($data);
    }

    public static function name(): string
    {
        return 'payment-type.update';
    }

    public static function description(): string|null
    {
        return 'update payment type';
    }

    public static function models(): array
    {
        return [PaymentType::class];
    }

    public function execute(): Model
    {
        $paymentType = PaymentType::query()
            ->whereKey($this->data['id'])
            ->first();

        $paymentType->fill($this->data);
        $paymentType->save();

        return $paymentType->withoutRelations()->fresh();
    }

    public function setRules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function validate(): static
    {
        $validator = Validator::make($this->data, $this->rules);
        $validator->addModel(new PaymentType());

        $this->data = $validator->validate();

        return $this;
    }
}
