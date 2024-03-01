<?php

namespace FluxErp\Actions\Commission;

use FluxErp\Actions\FluxAction;
use FluxErp\Models\Commission;
use FluxErp\Models\CommissionRate;
use FluxErp\Models\OrderPosition;
use FluxErp\Rulesets\Commission\CreateCommissionRuleset;

class CreateCommission extends FluxAction
{
    protected function boot(array $data): void
    {
        parent::boot($data);

        $this->rules = resolve_static(CreateCommissionRuleset::class, 'getRules');
    }

    public static function models(): array
    {
        return [Commission::class];
    }

    public function performAction(): Commission
    {
        if (! array_key_exists('commission_rate', $this->data)) {
            $commissionRateModel = app(CommissionRate::class)->query()
                ->whereKey($this->data['commission_rate_id'])
                ->first();

            $commissionRate = $commissionRateModel->commission_rate;
            $this->data['commission_rate'] = $commissionRateModel->toArray();
        } else {
            $commissionRate = $this->data['commission_rate'];
            $this->data['commission_rate'] = [
                'id' => null,
                'commission_rate' => $commissionRate,
            ];
        }

        if (! array_key_exists('total_net_price', $this->data)) {
            $orderPosition = app(OrderPosition::class)->query()
                ->whereKey($this->data['order_position_id'])
                ->first();

            $this->data['order_id'] = $orderPosition->order_id;
            $this->data['total_net_price'] = $orderPosition->total_net_price;
        }

        $this->data['commission'] = bcround(
            bcmul($this->data['total_net_price'], $commissionRate),
            2
        );

        $commission = app(Commission::class, ['attributes' => $this->data]);
        $commission->save();

        return $commission->fresh();
    }
}
