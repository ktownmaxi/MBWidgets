<?php

namespace FluxErp\Actions\SerialNumberRange;

use FluxErp\Actions\FluxAction;
use FluxErp\Models\SerialNumberRange;
use FluxErp\Rulesets\SerialNumberRange\CreateSerialNumberRangeRuleset;

class CreateSerialNumberRange extends FluxAction
{
    protected function boot(array $data): void
    {
        parent::boot($data);
        $this->rules = resolve_static(CreateSerialNumberRangeRuleset::class, 'getRules');
    }

    public static function models(): array
    {
        return [SerialNumberRange::class];
    }

    public function performAction(): SerialNumberRange
    {
        $this->data['current_number'] = array_key_exists('start_number', $this->data) ?
            --$this->data['start_number'] : 0;
        unset($this->data['start_number']);

        $serialNumberRange = app(SerialNumberRange::class, ['attributes' => $this->data]);
        $serialNumberRange->save();

        return $serialNumberRange->fresh();
    }
}
