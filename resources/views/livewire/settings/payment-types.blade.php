<x-modal name="edit-payment-type">
    <x-card>
        <div class="flex flex-col gap-4">
            <x-input wire:model="paymentType.name" :label="__('Name')" />
            <x-toggle wire:model.boolean="paymentType.is_active" :label="__('Is Active')" />
            <x-toggle wire:model.boolean="paymentType.is_default" :label="__('Is Default')" />
            <x-toggle wire:model.boolean="paymentType.is_purchase" :label="__('Is Purchase')" />
            <x-toggle wire:model.boolean="paymentType.is_sales" :label="__('Is Sales')" />
            <x-toggle wire:model.boolean="paymentType.is_direct_debit" :label="__('Is Direct Debit')" />
            <x-toggle wire:model.boolean="paymentType.requires_manual_transfer" :label="__('Requires Manual Transfer')" />
            <x-select
                :label="__('Client')"
                :options="$clients"
                option-value="id"
                option-label="name"
                multiselect
                autocomplete="off"
                wire:model="paymentType.clients"
            />
            <x-inputs.number wire:model="paymentType.payment_reminder_days_1" :label="__('Payment Reminder Days 1')" />
            <x-inputs.number wire:model="paymentType.payment_reminder_days_2" :label="__('Payment Reminder Days 2')" />
            <x-inputs.number wire:model="paymentType.payment_reminder_days_3" :label="__('Payment Reminder Days 3')" />
            <x-inputs.number wire:model="paymentType.payment_target" :label="__('Payment Target')" />
            <x-inputs.number wire:model="paymentType.payment_discount_target" :label="__('Payment Discount Target')" />
            <x-inputs.number wire:model="paymentType.payment_discount_percentage" :label="__('Payment Discount Percentage')" />
            <x-flux::editor wire:model="paymentType.description" :label="__('Description')" />
        </div>
        <x-slot:footer>
            <div class="flex justify-between gap-x-4">
                @if(resolve_static(\FluxErp\Actions\PaymentType\DeletePaymentType::class, 'canPerformAction', [false]))
                    <div x-bind:class="$wire.paymentType.id > 0 || 'invisible'">
                        <x-button
                            flat
                            negative
                            :label="__('Delete')"
                            x-on:click="close"
                            wire:click="delete().then((success) => { if(success) close()})"
                            wire:flux-confirm.icon.error="{{ __('wire:confirm.delete', ['model' => __('Payment Type')]) }}"
                        />
                    </div>
                @endif
                <div class="flex">
                    <x-button flat :label="__('Cancel')" x-on:click="close"/>
                    <x-button primary :label="__('Save')" wire:click="save().then((success) => { if(success) close()})"/>
                </div>
            </div>
        </x-slot:footer>
    </x-card>
</x-modal>
