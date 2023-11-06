<?php

namespace FluxErp\Livewire\DataTables;

use FluxErp\Actions\FormBuilderField\CreateFormBuilderField;
use FluxErp\Actions\FormBuilderField\UpdateFormBuilderField;
use FluxErp\Actions\FormBuilderForm\CreateFormBuilderForm;
use FluxErp\Actions\FormBuilderForm\UpdateFormBuilderForm;
use FluxErp\Actions\FormBuilderSection\CreateFormBuilderSection;
use FluxErp\Actions\FormBuilderSection\UpdateFormBuilderSection;
use FluxErp\Models\FormBuilderForm;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableButton;

class FormBuilderFormList extends DataTable
{
    protected string $model = FormBuilderForm::class;

    protected string $view = 'flux::livewire.settings.form-builder';
    public array $enabledCols = [
        'name',
        'description',
        'slug',
        'details',
        'is_active',
        'start_date',
        'end_date',
    ];

    public bool $showModal = false;

    public array $form = [
        'name' => null,
        'description' => null,
        'slug' => null,
        'is_active' => true,
        'start_date' => null,
        'end_date' => null,
    ];

    public array $formData = [];

    public array $fieldTypes = [
        ['value' => 'text', 'name' => 'Text'],
        ['value' => 'textarea', 'name' => 'Textarea'],
        ['value' => 'select', 'name' => 'Select'],
        ['value' => 'radio', 'name' => 'Radio'],
        ['value' => 'checkbox', 'name' => 'Checkbox'],
        ['value' => 'date', 'name' => 'Date'],
        ['value' => 'time', 'name' => 'Time'],
        ['value' => 'datetime', 'name' => 'Datetime'],
        ['value' => 'file', 'name' => 'File'],
        ['value' => 'image', 'name' => 'Image'],
        ['value' => 'number', 'name' => 'Number'],
        ['value' => 'email', 'name' => 'Email'],
        ['value' => 'password', 'name' => 'Password'],
        ['value' => 'range', 'name' => 'Range'],
    ];

    public function mount(): void
    {
        parent::mount();
    }

    public function getRowActions(): array
    {
        return [
            DataTableButton::make()
                ->label(__('Edit'))
                ->icon('pencil')
                ->color('primary')
                ->attributes([
                    'x-on:click' => '$wire.editItem(record.id)',
                ]),
            DataTableButton::make()
                ->label(__('Delete'))
                ->icon('trash')
                ->color('negative')
                ->attributes([
                    'x-on:click' => '$wire.deleteItem(record.id)',
                    'wire:loading.attr' => 'disabled',
                ]),
        ];
    }

    public function getTableActions(): array
    {
        return [
            DataTableButton::make()
                ->label(__('Create'))
                ->icon('plus')
                ->color('primary')
                ->attributes([
                    'x-on:click' => '$wire.editItem(null)',
                ]),
        ];
    }

    public function deleteItem(FormBuilderForm $form): void
    {
        //        $this->skipRender();
        //
        //        try {
        //            DeleteFormBuilderForm::make($form->toArray())
        //                ->checkPermission()
        //                ->validate()
        //                ->execute();
        //        } catch (\Exception $e) {
        //            exception_to_notifications($e, $this);
        //
        //            return;
        //        }
        //
        //        $this->loadData();
    }


    public function editItem($id = null)
    {
        $id == null ?: $this->form = FormBuilderForm::find($id)->toArray();
        $this->showModal = true;
    }

    public function saveItem()
    {
        $action = ($this->form['id'] ?? false) ? UpdateFormBuilderForm::class : CreateFormBuilderForm::class;
        try {
            $action::make($this->form)
                ->validate()
                ->checkPermission()
                ->execute();

            $this->reset('form');
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);
        }

        foreach ($this->formData as $section) {
            $section['form_id'] = $this->form['id'];
            try {
                $action = ($section['id'] ?? false) ? UpdateFormBuilderSection::class : CreateFormBuilderSection::class;
                $action::make($section)
                    ->validate()
                    ->checkPermission()
                    ->execute();
            } catch (ValidationException|UnauthorizedException $e) {
                exception_to_notifications($e, $this);
            }

            foreach ($section['fields'] as $field) {
                $field->section_id = $section['id'];
                try {
                    $action = ($field['id'] ?? false) ? UpdateFormBuilderField::class : CreateFormBuilderField::class;
                    $action::make($field)
                        ->validate()
                        ->checkPermission()
                        ->execute();
                } catch (ValidationException|UnauthorizedException $e) {
                    exception_to_notifications($e, $this);
                }
            }
        }

        $this->showModal = false;
    }

    public function boot(): void
    {
        // override boot to force rendering
    }

    public function addSection()
    {
        $this->formData[] = [
            'id' => null,
            'name' => null,
            'ordering' => null,
            'columns' => null,
            'description' => null,
            'icon' => null,
            'aside' => false,
            'compact' => false,
        ];
    }

    public function deleteSection()
    {

    }

    public function addFormField(int $index)
    {

        $this->formData[$index]['fields'][] = [
            'id' => null,
            'name' => null,
            'description' => null,
            'type' => 'text',
            'ordering' => null,
            'options' => true,
        ];
    }

    public function saveFormField()
    {

    }

    public function deleteFormField(int $index)
    {
        unset($this->formData[$index]);
    }


    public function debug()
    {
        dd($this->form, $this->formData);
    }
}
