<?php

namespace FluxErp\Actions\Order;

use FluxErp\Actions\FluxAction;
use FluxErp\Actions\OrderPosition\CreateOrderPosition;
use FluxErp\Http\Requests\ReplicateOrderRequest;
use FluxErp\Models\Order;
use FluxErp\Models\OrderPosition;
use Illuminate\Validation\ValidationException;

class ReplicateOrder extends FluxAction
{
    protected function boot(array $data): void
    {
        parent::boot($data);
        $this->rules = (new ReplicateOrderRequest())->rules();
    }

    public static function models(): array
    {
        return [Order::class];
    }

    public function performAction(): Order
    {
        $getOrderPositionsFromOrigin = is_null(data_get($this->data, 'order_positions'));

        $originalOrder = Order::query()
            ->whereKey($this->data['id'])
            ->when($getOrderPositionsFromOrigin, fn ($query) => $query->with('orderPositions'))
            ->first()
            ->toArray();

        $orderData = array_merge(
            $originalOrder,
            $this->data,
        );

        unset(
            $orderData['id'],
            $orderData['uuid'],
            $orderData['agent_id'],
            $orderData['bank_connection_id'],
            $orderData['address_invoice'],
            $orderData['address_delivery'],
            $orderData['state'],
            $orderData['payment_state'],
            $orderData['delivery_state'],
            $orderData['invoice_number'],
            $orderData['invoice_date'],
            $orderData['order_number'],
            $orderData['order_date'],
            $orderData['is_locked'],
            $orderData['is_imported'],
            $orderData['is_confirmed'],
            $orderData['is_paid'],
        );

        if ($originalOrder['parent_id'] === $orderData['parent_id']) {
            unset($orderData['parent_id']);
        }

        $order = CreateOrder::make($orderData)
            ->checkPermission()
            ->validate()
            ->execute();

        if (! $getOrderPositionsFromOrigin) {
            $replicateOrderPositions = collect($this->data['order_positions']);
            $orderPositions = OrderPosition::query()
                ->whereIntegerInRaw('id', array_column($this->data['order_positions'], 'id'))
                ->get()
                ->map(function (OrderPosition $orderPosition) use ($replicateOrderPositions) {
                    $position = $replicateOrderPositions->first(fn ($item) => $item['id'] === $orderPosition->id);

                    $orderPosition->origin_position_id = $orderPosition->id;
                    $orderPosition->amount = $position['amount'];

                    return $orderPosition;
                })
                ->toArray();
        } else {
            $orderPositions = $originalOrder['order_positions'] ?? [];
        }

        foreach ($orderPositions as $orderPosition) {
            $orderPosition['order_id'] = $order->id;

            unset(
                $orderPosition['id'],
                $orderPosition['uuid'],
                $orderPosition['amount_packed_products'],
            );

            CreateOrderPosition::make($orderPosition)
                ->checkPermission()
                ->validate()
                ->execute();
        }

        $order->calculatePrices()->save();

        return $order->withoutRelations()->fresh();
    }

    protected function validateData(): void
    {
        parent::validateData();

        $orderPositions = data_get($this->data, 'order_positions', []);
        $ids = array_column($orderPositions, 'id');

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'order_positions' => ['No duplicate order position ids allowed.'],
            ])->errorBag('replicateOrder');
        }

        if ($orderPositions) {
            if (OrderPosition::query()
                ->whereIntegerInRaw('id', $ids)
                ->where('order_id', '!=', $this->data['id'])
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'order_positions' => ['Only order positions from given order allowed.'],
                ])->errorBag('replicateOrder');
            }

            $siblings = OrderPosition::query()
                ->whereIntegerInRaw('order_positions.id', $ids)
                ->siblings()
                ->get();

            $errors = [];
            foreach ($orderPositions as $key => $orderPosition) {
                if ($item = $siblings
                    ->where('id', $orderPosition['id'])
                    ->where('totalAmount', '<', $orderPosition['amount'])
                    ->first()
                ) {
                    $errors += [
                        'order_positions.' . $key . '.amount' => [
                            __('validation.max.numeric', ['attribute' => __('amount'), 'max' => $item['totalAmount']]),
                        ],
                    ];
                }
            }

            if ($errors) {
                throw ValidationException::withMessages($errors)->errorBag('replicateOrder');
            }
        }
    }
}
