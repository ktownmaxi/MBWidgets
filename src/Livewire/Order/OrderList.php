<?php

namespace FluxErp\Livewire\Order;

use FluxErp\Actions\Order\DeleteOrder;
use FluxErp\Actions\Printing;
use FluxErp\Livewire\Forms\OrderForm;
use FluxErp\Models\Client;
use FluxErp\Models\Contact;
use FluxErp\Models\Language;
use FluxErp\Models\Media;
use FluxErp\Models\Order;
use FluxErp\Models\OrderType;
use FluxErp\Models\PaymentType;
use FluxErp\Models\PriceList;
use FluxErp\Support\OrderCollection;
use FluxErp\View\Printing\PrintableView;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportRedirects\Redirector;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Spatie\MediaLibrary\Support\MediaStream;
use Spatie\Permission\Exceptions\UnauthorizedException;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableButton;

class OrderList extends \FluxErp\Livewire\DataTables\OrderList
{
    protected string $view = 'flux::livewire.order.order-list';

    public ?string $cacheKey = 'order.order-list';

    public OrderForm $order;

    public OrderCollection $orders;

    public array $printLayouts = [];

    public array $selectedPrintLayouts = [];

    public function getTableActions(): array
    {
        return [
            DataTableButton::make()
                ->color('primary')
                ->label(__('New order'))
                ->icon('plus')
                ->attributes([
                    'x-on:click' => "\$openModal('create-order')",
                ]),
        ];
    }

    public function getViewData(): array
    {
        return array_merge(
            parent::getViewData(),
            [
                'priceLists' => PriceList::query()
                    ->get(['id', 'name'])
                    ->toArray(),
                'paymentTypes' => PaymentType::query()
                    ->get(['id', 'name'])
                    ->toArray(),
                'languages' => Language::query()
                    ->get(['id', 'name'])
                    ->toArray(),
                'clients' => Client::query()
                    ->where('is_active', true)
                    ->get(['id', 'name'])
                    ->toArray(),
                'orderTypes' => OrderType::query()
                    ->where('is_hidden', false)
                    ->where('is_active', true)
                    ->get(['id', 'name'])
                    ->toArray(),
            ]
        );
    }

    public function getSelectedActions(): array
    {
        return [
            DataTableButton::make()
                ->icon('document-text')
                ->label(__('Create Documents'))
                ->color('primary')
                ->wireClick('openCreateDocumentsModal'),
            DataTableButton::make()
                ->icon('trash')
                ->label(__('Delete'))
                ->color('negative')
                ->when(fn () => DeleteOrder::canPerformAction(false))
                ->attributes([
                    'wire:click' => 'delete',
                    'wire:confirm.icon.error' => __('wire:confirm.delete', ['model' => __('Orders')]),
                ]),
        ];
    }

    #[Renderless]
    public function fetchContactData(): void
    {
        $contact = Contact::query()
            ->whereKey($this->order->contact_id)
            ->first();

        $this->order->client_id = $contact->client_id ?: $this->order->client_id;
        $this->order->agent_id = $contact->agent_id ?: $this->order->agent_id;
        $this->order->address_invoice_id = $contact->address_invoice_id;
        $this->order->address_delivery_id = $contact->address_delivery_id;
        $this->order->price_list_id = $contact->price_list_id ?: $this->order->price_list_id;
        $this->order->payment_type_id = $contact->payment_type_id ?: $this->order->payment_type_id;
        $this->order->address_invoice_id = $contact->invoice_address_id ?: $this->order->address_invoice_id;
        $this->order->address_delivery_id = $contact->delivery_address_id ?: $this->order->address_delivery_id;
    }

    public function save(): false|Redirector
    {
        try {
            $this->order->save();
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);

            return false;
        }

        return redirect()->to(route('orders.id', $this->order->id));
    }

    public function openCreateDocumentsModal(): void
    {
        $this->orders = Order::query()
            ->whereIntegerInRaw('id', $this->selected)
            ->get(['id', 'order_type_id']);

        $this->printLayouts = $this->orders->printLayouts();

        $this->js(<<<'JS'
            $openModal('create-documents');
        JS);

        $this->forceRender();
    }

    #[Renderless]
    public function createDocuments(): null|MediaStream|Media
    {
        $downloadIds = [];
        $printIds = [];
        $mailMessages = [];
        foreach ($this->orders as $order) {
            $order = Order::query()
                ->whereKey($order->id)
                ->first();

            $mailAttachments = [];
            $hash = md5(json_encode($order->toArray()) . json_encode($order->orderPositions->toArray()));

            $createDocuments = [];
            foreach ($this->selectedPrintLayouts as $type => $selectedPrintLayout) {
                $this->selectedPrintLayouts[$type] = array_intersect_key(
                    $order->resolvePrintViews(),
                    array_filter($selectedPrintLayout)
                );
                $createDocuments = array_unique(
                    array_merge(
                        $createDocuments,
                        array_keys($this->selectedPrintLayouts[$type]))
                );
            }

            // create the documents
            foreach ($createDocuments as $createDocument) {
                $media = $order->getMedia(
                    $createDocument,
                    fn (BaseMedia $media) => $media->getCustomProperty('hash') === $hash
                )
                    ->last();

                if (! $media || ($this->selectedPrintLayouts['force'][$createDocument] ?? false)) {
                    try {
                        /** @var PrintableView $file */
                        $file = Printing::make([
                            'model_type' => Order::class,
                            'model_id' => $order->id,
                            'view' => $createDocument,
                        ])->checkPermission()->validate()->execute();

                        $media = $file->attachToModel();
                        $media->setCustomProperty('hash', $hash)->save();
                    } catch (ValidationException|UnauthorizedException $e) {
                        exception_to_notifications($e, $this);

                        continue;
                    }
                }

                if ($this->selectedPrintLayouts['download'][$createDocument] ?? false) {
                    $downloadIds[] = $media->id;
                }

                if ($this->selectedPrintLayouts['print'][$createDocument] ?? false) {
                    // TODO: add to print queue for spooler
                    $printIds[] = $media->id;
                }

                if ($this->selectedPrintLayouts['email'][$createDocument] ?? false) {
                    $mailAttachments[] = [
                        'name' => $media->file_name,
                        'id' => $media->id,
                    ];
                }
            }

            if (($this->selectedPrintLayouts['email'] ?? false) && $mailAttachments) {
                $order->refresh();
                $to = [];

                $to[] = in_array('invoice', $createDocuments) && $order->contact->invoiceAddress
                    ? $order->contact->invoiceAddress->email
                    : $order->contact->mainAddress->email;

                if (array_keys($this->selectedPrintLayouts['email']) !== ['invoice']
                    && $order->contact->mainAddress->email
                ) {
                    $to[] = $order->contact->mainAddress->email;
                }

                $mailMessages[] = [
                    'to' => array_unique($to),
                    'subject' => html_entity_decode($order->orderType->mail_subject) ?:
                        $order->orderType->name . ' ' . $order->order_number,
                    'attachments' => $mailAttachments,
                    'html_body' => html_entity_decode($order->orderType->mail_body),
                    'blade_parameters_serialized' => true,
                    'blade_parameters' => serialize(['order' => $order]),
                    'communicatable_type' => Order::class,
                    'communicatable_id' => $order->id,
                ];
            }
        }

        if ($mailMessages) {
            $this->dispatch('createMany', mailMessages: $mailMessages)->to('edit-mail');
        }

        if ($downloadIds) {
            $files = Media::query()
                ->whereIntegerInRaw('id', $downloadIds)
                ->get();

            if ($files->count() === 1) {
                return $files->first();
            }

            return MediaStream::create(__('Order_collection') . '_' . now()->toDateString() . '.zip')
                ->addMedia($files);
        }

        return null;
    }
}
