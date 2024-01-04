<?php

namespace FluxErp\States;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Spatie\ModelStates\State as BaseState;

abstract class State extends BaseState implements Arrayable
{
    public function toArray(): array|string
    {
        return $this->__toString();
    }

    public function badge(): string
    {
        return Blade::render(html_entity_decode('<x-badge :$label :$color />'), [
            'color' => $this->color(),
            'label' => __(Str::headline($this->__toString())),
        ]);
    }
}
