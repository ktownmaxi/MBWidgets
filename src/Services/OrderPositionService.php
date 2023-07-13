<?php

namespace FluxErp\Services;

use FluxErp\Actions\OrderPosition\CreateOrderPosition;
use FluxErp\Actions\OrderPosition\DeleteOrderPosition;
use FluxErp\Actions\OrderPosition\FillOrderPositions;
use FluxErp\Actions\OrderPosition\UpdateOrderPosition;
use FluxErp\Helpers\ResponseHelper;
use FluxErp\Models\OrderPosition;
use Illuminate\Validation\ValidationException;

class OrderPositionService
{
    public function create(array $data): OrderPosition
    {
        return CreateOrderPosition::make($data)->execute();
    }

    public function update(array $data): array
    {
        if (! array_is_list($data)) {
            $data = [$data];
        }

        $responses = [];
        foreach ($data as $key => $item) {
            try {
                $responses[] = ResponseHelper::createArrayResponse(
                    statusCode: 200,
                    data: $orderPosition = UpdateOrderPosition::make($item)->validate()->execute(),
                    additions: ['id' => $orderPosition->id]
                );
            } catch (ValidationException $e) {
                $responses[] = ResponseHelper::createArrayResponse(
                    statusCode: 422,
                    data: $e->errors(),
                    additions: [
                        'id' => array_key_exists('id', $item) ? $item['id'] : null,
                    ]
                );

                unset($data[$key]);
            }
        }

        $statusCode = count($responses) === count($data) ? 200 : (count($data) < 1 ? 422 : 207);

        return ResponseHelper::createArrayResponse(
            statusCode: $statusCode,
            data: $responses,
            statusMessage: $statusCode === 422 ? null : 'order-position(s) updated',
            bulk: true
        );
    }

    public function delete(string $id): array
    {
        try {
            DeleteOrderPosition::make(['id' => $id])->validate()->execute();
        } catch (ValidationException $e) {
            return ResponseHelper::createArrayResponse(
                statusCode: 404,
                data: $e->errors()
            );
        }

        return ResponseHelper::createArrayResponse(
            statusCode: 204,
            statusMessage: 'order-position(s) deleted'
        );
    }

    public function fill(int $orderId, array $data, bool $simulate = false): array
    {
        try {
            $orderPositions = FillOrderPositions::make([
                'order_id' => $orderId,
                'order_positions' => $data,
                'simulate' => $simulate,
            ])->validate()->execute();
        } catch (ValidationException $e) {
            return ResponseHelper::createArrayResponse(
                statusCode: 422,
                data: $e->errors()
            );
        }

        return ResponseHelper::createArrayResponse(
            statusCode: 200,
            data: $orderPositions
        );
    }
}
