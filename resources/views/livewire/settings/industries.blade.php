<x-modal name="edit-industry">
    <x-card>
        <div class="flex flex-col gap-4">
            <x-input wire:model="industryForm.name" :label="__('Name')"/>
        </div>
        <x-slot:footer>
            <div class="flex justify-end gap-1.5">
                <x-button flat :label="__('Cancel')" x-on:click="close"/>
                <x-button primary :label="__('Save')" wire:click="save().then((success) => { if(success) close()})"/>
            </div>
        </x-slot:footer>
    </x-card>
</x-modal>
