<?php

namespace FluxErp\Livewire\Contact;

use FluxErp\Livewire\Forms\ContactForm;
use FluxErp\Livewire\Order\OrderList;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Renderless;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableButton;

class Orders extends OrderList
{
    protected string $view = 'flux::livewire.contact.orders';

    #[Modelable]
    public ContactForm $contact;

    protected function getBuilder(Builder $builder): Builder
    {
        return $builder->where('contact_id', $this->contact->id);
    }

    protected function getTableActions(): array
    {
        return [
            DataTableButton::make()
                ->label(__('Balance Statement'))
                ->wireClick('$parent.openCreateDocumentsModal()'),
            DataTableButton::make()
                ->icon('plus')
                ->color('primary')
                ->label(__('New order'))
                ->wireClick('createOrder'),
        ];
    }

    #[Renderless]
    public function createOrder(): void
    {
        $this->order->reset();
        $this->order->contact_id = $this->contact->id;

        $this->js(<<<'JS'
            $openModal('create-order');
        JS);
    }

    #[Renderless]
    public function getCacheKey(): string
    {
        return parent::getCacheKey() . $this->contact->id;
    }
}
